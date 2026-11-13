<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Usuarios\ActualizarUsuarioAction;
use App\Modules\Identidad\Application\Usuarios\GuardaAdministradores;
use App\Modules\Identidad\Application\Usuarios\ReglasDeJefatura;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;

/** F-58 · F-61 · W-15 · W-39 · HU-006 (RN-IDE-20…27). */
final class UsuariosEdicionTest extends IdentidadTestCase
{
    public function test_listado_muestra_activos_y_filtra_inactivos_y_busqueda(): void
    {
        $admin = $this->administrador();
        $this->usuarioDe('Compras', ['nombre' => 'Carla', 'email' => 'carla@empresa-ejemplo.com']);
        User::factory()->enSector('Compras')->inactivo()->create(['nombre' => 'Hugo', 'email' => 'hugo@empresa-ejemplo.com']);

        $this->actingAs($admin)->getJson('/api/usuarios')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.nombre', 'Carla');
        $this->actingAs($admin)->getJson('/api/usuarios?activo=false')->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'Hugo');
        $this->actingAs($admin)->getJson('/api/usuarios?activo=todos')->assertJsonCount(3, 'data');
        $this->actingAs($admin)->getJson('/api/usuarios?buscar=carla@')->assertJsonCount(1, 'data');
    }

    public function test_listar_usuarios_exige_usuarios_permisos_ver(): void
    {
        $this->actingAs($this->usuarioDe('Dirección'))->getJson('/api/usuarios')->assertOk();
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/usuarios')->assertForbidden()->assertJsonPath('data', null);
    }

    public function test_sin_permiso_no_ve_el_detalle_de_otro_usuario_por_id(): void
    {
        $carla = $this->usuarioDe('Compras');
        $laura = $this->usuarioDe('Ingeniería de proyectos');

        $this->actingAs($laura)->getJson("/api/usuarios/{$carla->id}")->assertForbidden()->assertJsonMissingPath('data.email');
        $this->actingAs($laura)->getJson('/api/usuarios/999999')->assertForbidden();
        $this->actingAs($this->administrador())->getJson("/api/usuarios/{$carla->id}")->assertOk()->assertJsonPath('data.id', $carla->id);
    }

    public function test_usuarios_ver_no_puede_editar_un_usuario(): void
    {
        // Caso borde 17: con Usuarios y permisos = Ver lista y ve el detalle, pero PATCH → 403.
        $this->celda('Jefatura de Administración', 'usuarios_permisos', 'ver');
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $soloVe = $this->usuarioDe('Jefatura de Administración');

        $this->actingAs($soloVe)->getJson("/api/usuarios/{$carla->id}")->assertOk();
        $this->actingAs($soloVe)->patchJson("/api/usuarios/{$carla->id}", ['nombre' => 'Y'])->assertForbidden();
        $this->actingAs($this->usuarioDe('Gerencia de Operaciones'))->patchJson("/api/usuarios/{$carla->id}", ['nombre' => 'X'])
            ->assertOk();
        $this->assertDatabaseHas('users', ['id' => $carla->id, 'nombre' => 'X']);
    }

    public function test_jefe_no_puede_ser_el_mismo_usuario(): void
    {
        $carla = $this->usuarioDe('Compras');

        $this->actingAs($this->administrador())->patchJson("/api/usuarios/{$carla->id}", ['jefe_id' => $carla->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.jefe_id.0', ReglasDeJefatura::MISMA_PERSONA);
    }

    public function test_jefe_no_puede_armar_un_circulo(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
        $laura = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura', 'jefe_id' => $hernan->id]);
        $pablo = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Pablo', 'jefe_id' => $laura->id]);
        $admin = $this->administrador();

        $this->actingAs($admin)->patchJson("/api/usuarios/{$hernan->id}", ['jefe_id' => $laura->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.jefe_id.0', 'Así se arma un círculo: Laura ya depende de Hernán.');
        $this->actingAs($admin)->patchJson("/api/usuarios/{$hernan->id}", ['jefe_id' => $pablo->id])
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $hernan->id, 'jefe_id' => null]);

        $this->actingAs($admin)->patchJson("/api/usuarios/{$pablo->id}", ['jefe_id' => $hernan->id])
            ->assertOk()->assertJsonPath('data.jefe.id', $hernan->id);
        $this->actingAs($admin)->patchJson("/api/usuarios/{$pablo->id}", ['jefe_id' => null])
            ->assertOk()->assertJsonPath('data.jefe', null);
    }

    public function test_desactivar_no_borra_y_conserva_historial(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $admin = $this->administrador();

        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['activo' => false])
            ->assertOk()->assertJsonPath('data.activo', false);

        $this->assertDatabaseHas('users', ['id' => $carla->id, 'activo' => false]);
        $registros = $this->actividades(User::class, 'editado');
        $this->assertCount(1, $registros);
        $this->assertSame($carla->id, $registros[0]->sujeto_id);
        $this->assertSame($admin->id, $registros[0]->user_id);
        $this->assertSame(['activo' => ['antes' => true, 'despues' => false]], $registros[0]->cambios);

        // Inactiva no recibe código; reactivada, vuelve a entrar con su mail (HU-006 criterio 5, ADR-015).
        $this->app['auth']->forgetGuards();
        $this->assertNull($this->pedirCodigo('carla@empresa-ejemplo.com'));
        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['activo' => true])->assertOk();
        $this->app['auth']->forgetGuards();
        $codigo = (string) $this->pedirCodigo('carla@empresa-ejemplo.com');
        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => 'carla@empresa-ejemplo.com', 'codigo' => $codigo])
            ->assertOk();
    }

    public function test_usuario_inactivo_sale_de_las_opciones(): void
    {
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $hugo = User::factory()->enSector('Compras')->inactivo()->create(['nombre' => 'Hugo']);

        $ids = array_column((array) $this->actingAs($carla)->getJson('/api/usuarios/opciones')->assertOk()->json('data'), 'id');

        $this->assertContains($carla->id, $ids);
        $this->assertNotContains($hugo->id, $ids);
    }

    public function test_opciones_de_usuarios_exigen_sesion(): void
    {
        $this->getJson('/api/usuarios/opciones')->assertUnauthorized();
    }

    public function test_desactivar_jefe_informa_personas_y_obras_sin_bloquear(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
        $this->usuarioDe('Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $this->usuarioDe('Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $admin = $this->administrador();

        $this->actingAs($admin)->getJson("/api/usuarios/{$hernan->id}")
            ->assertJsonPath('data.personas_a_cargo', 2)
            ->assertJsonPath('data.obras_como_responsable', 0);

        $this->actingAs($admin)->patchJson("/api/usuarios/{$hernan->id}", ['activo' => false])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.personas_a_cargo', 2);
        $this->assertSame(2, User::query()->where('jefe_id', $hernan->id)->count());
    }

    public function test_nadie_se_desactiva_a_si_mismo(): void
    {
        $admin = $this->administrador();
        $this->usuarioDe('Dirección');

        $this->actingAs($admin)->patchJson("/api/usuarios/{$admin->id}", ['activo' => false])
            ->assertUnprocessable()
            ->assertJsonPath('errors.activo.0', ActualizarUsuarioAction::NO_SE_DESACTIVA_A_SI_MISMO);
    }

    public function test_no_queda_sin_administrador_de_usuarios(): void
    {
        // Caso borde 6: Diego es el único con Usuarios = Editar y se pasa a Compras.
        $diego = $this->administrador();
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');

        $this->actingAs($diego)->patchJson("/api/usuarios/{$diego->id}", ['sector_id' => $compras])
            ->assertUnprocessable()
            ->assertJsonPath('errors.sector_id.0', GuardaAdministradores::SIN_ADMINISTRADOR);
        $this->assertDatabaseHas('users', ['id' => $diego->id, 'sector_id' => $diego->sector_id]);

        $this->usuarioDe('Gerencia de Operaciones');
        $this->actingAs($diego)->patchJson("/api/usuarios/{$diego->id}", ['sector_id' => $compras])->assertOk();
    }

    public function test_guarda_de_administradores_cuenta_a_cualquier_activo_aunque_nunca_haya_ingresado(): void
    {
        // ADR-015: no hay "no eligió contraseña"; cualquier activo puede entrar con su mail, así que cuenta.
        $diego = $this->administrador();
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');
        $direccion = Sector::query()->where('nombre', 'Dirección')->valueOrFail('id');

        $this->actingAs($diego)->postJson('/api/usuarios', $this->datosDeAlta(['nombre' => 'Eva', 'email' => 'eva@empresa-ejemplo.com', 'sector_id' => $direccion]))
            ->assertCreated();
        $eva = User::query()->where('email', 'eva@empresa-ejemplo.com')->firstOrFail();

        $this->actingAs($diego)->patchJson("/api/usuarios/{$eva->id}", ['activo' => false])->assertOk();
        $this->actingAs($diego)->patchJson("/api/usuarios/{$diego->id}", ['sector_id' => $compras])
            ->assertUnprocessable()
            ->assertJsonPath('errors.sector_id.0', GuardaAdministradores::SIN_ADMINISTRADOR);

        $this->actingAs($diego)->patchJson("/api/usuarios/{$eva->id}", ['activo' => true])->assertOk();
        $this->actingAs($diego)->patchJson("/api/usuarios/{$diego->id}", ['sector_id' => $compras])->assertOk();
    }

    public function test_cambio_de_sector_vale_desde_el_proximo_pedido(): void
    {
        $carla = $this->usuarioDe('Compras');
        $this->actingAs($carla)->getJson('/api/usuarios')->assertForbidden();

        $gerencia = Sector::query()->where('nombre', 'Gerencia de Operaciones')->valueOrFail('id');
        $this->actingAs($this->administrador())->patchJson("/api/usuarios/{$carla->id}", ['sector_id' => $gerencia])->assertOk();

        $this->actingAs($carla->fresh() ?? $carla)->getJson('/api/usuarios')->assertOk();
    }

    public function test_editar_mail_a_uno_existente_es_rechazado(): void
    {
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla', 'email' => 'carla@empresa-ejemplo.com']);
        $laura = $this->usuarioDe('Compras', ['email' => 'laura@empresa-ejemplo.com']);
        $admin = $this->administrador();

        $this->actingAs($admin)->patchJson("/api/usuarios/{$laura->id}", ['email' => 'carla@empresa-ejemplo.com'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Ese mail ya es de otro usuario (Carla).');
        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['email' => 'CARLA@empresa-ejemplo.com '])
            ->assertOk()->assertJsonPath('data.email', 'carla@empresa-ejemplo.com');
    }
}
