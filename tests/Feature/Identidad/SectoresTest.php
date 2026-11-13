<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Sectores\SacarSectorAction;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;

/** F-59 · W-40 · HU-007 (RN-IDE-28…33). */
final class SectoresTest extends IdentidadTestCase
{
    private const ORGANIGRAMA = [
        'Dirección', 'Calidad', 'Gerencia de Operaciones', 'Jefatura de Proyectos', 'Ingeniería de proyectos',
        'Seguridad e Higiene', 'Gerencia de Administración', 'Jefatura de Administración', 'Compras', 'RRHH',
        'Pañol y Logística',
    ];

    public function test_migracion_crea_los_once_sectores_del_organigrama(): void
    {
        $response = $this->actingAs($this->administrador())->getJson('/api/sectores')->assertOk();

        $this->assertSame(self::ORGANIGRAMA, array_column((array) $response->json('data'), 'nombre'));
        $response->assertJsonPath('data.10', ['id' => Sector::query()->where('nombre', 'Pañol y Logística')->valueOrFail('id'), 'nombre' => 'Pañol y Logística', 'usuarios_activos' => 0, 'activo' => true]);
        $this->assertSame(['Dirección'], Sector::query()->where('es_direccion', true)->pluck('nombre')->all());
    }

    public function test_listar_sectores_exige_sesion(): void
    {
        $this->getJson('/api/sectores')->assertUnauthorized();
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/sectores')->assertOk();
    }

    public function test_nombre_de_sector_unico_sin_mayusculas_ni_tildes(): void
    {
        $admin = $this->administrador();
        $compras = Sector::query()->where('nombre', 'Compras')->firstOrFail();
        $rrhh = Sector::query()->where('nombre', 'RRHH')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/sectores', ['nombre' => 'cómpras '])
            ->assertUnprocessable()->assertJsonPath('errors.nombre.0', 'Ya hay un sector con ese nombre.');
        $this->actingAs($admin)->patchJson("/api/sectores/{$rrhh->id}", ['nombre' => 'compras '])
            ->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/sectores/{$compras->id}", ['nombre' => 'COMPRAS'])
            ->assertOk()->assertJsonPath('data.nombre', 'COMPRAS');
    }

    public function test_sector_nuevo_nace_con_pedidos_y_equipos_editar_y_obras_ver(): void
    {
        $this->actingAs($this->administrador())->postJson('/api/sectores', ['nombre' => 'Laboratorio'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Laboratorio')
            ->assertJsonPath('data.usuarios_activos', 0);

        $sector = Sector::query()->where('nombre', 'Laboratorio')->firstOrFail();
        $celdas = Permiso::query()->with('funcion')->where('sector_id', $sector->id)->get()
            ->mapWithKeys(fn (Permiso $celda): array => [$celda->funcion->clave => $celda->nivel->value.'·'.$celda->alcance->value]);

        $this->assertCount(12, $celdas);
        $this->assertSame('editar·todas', $celdas['pedidos']);
        $this->assertSame('editar·todas', $celdas['equipos_reparacion']);
        $this->assertSame('ver·todas', $celdas['obras']);
        $this->assertSame(9, $celdas->filter(fn (string $celda): bool => $celda === 'sin_acceso·todas')->count());
        $creado = $this->actividades(Sector::class, 'creado');
        $this->assertCount(1, $creado);
        $this->assertSame($sector->id, $creado[0]->sujeto_id);
    }

    public function test_doble_envio_del_alta_de_sector_crea_uno_solo(): void
    {
        $admin = $this->administrador();
        $clave = ['Idempotency-Key' => '0b6f9e0e-6a35-4c4c-9a57-6f1f2c1d3e4a'];

        $primero = $this->actingAs($admin)->withHeaders($clave)->postJson('/api/sectores', ['nombre' => 'Laboratorio'])->assertCreated();
        $segundo = $this->actingAs($admin)->withHeaders($clave)->postJson('/api/sectores', ['nombre' => 'Laboratorio'])
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame($primero->json('data.id'), $segundo->json('data.id'));
        $this->assertSame(1, Sector::query()->where('nombre', 'Laboratorio')->count());
        $this->assertCount(1, $this->actividades(Sector::class, 'creado'));
    }

    public function test_no_se_saca_un_sector_con_usuarios_activos(): void
    {
        $calidad = Sector::query()->where('nombre', 'Calidad')->firstOrFail();
        $this->usuarioDe('Calidad');
        $this->usuarioDe('Calidad');

        $this->actingAs($this->administrador())->deleteJson("/api/sectores/{$calidad->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.sector.0', 'Antes de sacar Calidad, pasá a sus 2 usuarios a otro sector.');
        $this->assertDatabaseHas('sectores', ['id' => $calidad->id, 'activo' => true]);
    }

    public function test_sector_sin_historia_se_borra_y_con_historia_se_archiva(): void
    {
        $admin = $this->administrador();
        $rrhh = Sector::query()->where('nombre', 'RRHH')->firstOrFail();
        $calidad = Sector::query()->where('nombre', 'Calidad')->firstOrFail();
        User::factory()->enSector('Calidad')->inactivo()->create();

        $this->actingAs($admin)->deleteJson("/api/sectores/{$rrhh->id}")
            ->assertOk()->assertJsonPath('data.resultado', SacarSectorAction::BORRADO);
        $this->assertDatabaseMissing('sectores', ['id' => $rrhh->id]);
        $this->assertDatabaseMissing('permisos', ['sector_id' => $rrhh->id]);

        $this->actingAs($admin)->deleteJson("/api/sectores/{$calidad->id}")
            ->assertOk()->assertJsonPath('data.resultado', SacarSectorAction::ARCHIVADO);
        $this->assertDatabaseHas('sectores', ['id' => $calidad->id, 'activo' => false, 'nombre' => 'Calidad']);

        $sectores = array_column((array) $this->actingAs($admin)->getJson('/api/sectores')->json('data'), 'nombre');
        $this->assertNotContains('Calidad', $sectores);
        $this->assertNotContains('RRHH', $sectores);
        $this->assertContains('Calidad', array_column((array) $this->actingAs($admin)->getJson('/api/sectores?incluir_archivados=1')->json('data'), 'nombre'));
        $this->assertNotContains($calidad->id, array_column((array) $this->actingAs($admin)->getJson('/api/permisos')->json('data.sectores'), 'id'));

        // El nombre de un archivado queda libre para un sector nuevo (§7).
        $this->actingAs($admin)->postJson('/api/sectores', ['nombre' => 'Calidad'])->assertCreated();
    }

    public function test_renombrar_sector_conserva_usuarios_y_permisos(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->firstOrFail();
        $carla = $this->usuarioDe('Compras');
        $celdasAntes = Permiso::query()->where('sector_id', $compras->id)->orderBy('funcion_id')->get(['funcion_id', 'nivel', 'alcance'])->toArray();

        $this->actingAs($this->administrador())->patchJson("/api/sectores/{$compras->id}", ['nombre' => 'Compras y Abastecimiento'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Compras y Abastecimiento')
            ->assertJsonPath('data.usuarios_activos', 1);

        $this->assertDatabaseHas('users', ['id' => $carla->id, 'sector_id' => $compras->id]);
        $this->assertSame($celdasAntes, Permiso::query()->where('sector_id', $compras->id)->orderBy('funcion_id')->get(['funcion_id', 'nivel', 'alcance'])->toArray());
        $this->actingAs($carla)->getJson('/api/me')->assertJsonPath('data.sector.nombre', 'Compras y Abastecimiento');
    }

    public function test_sin_usuarios_permisos_editar_no_agrega_renombra_ni_saca_sectores(): void
    {
        $rrhh = Sector::query()->where('nombre', 'RRHH')->firstOrFail();
        $jefatura = $this->usuarioDe('Jefatura de Administración');

        $this->actingAs($jefatura)->postJson('/api/sectores', ['nombre' => 'Laboratorio'])->assertForbidden();
        $this->actingAs($jefatura)->patchJson("/api/sectores/{$rrhh->id}", ['nombre' => 'Personal'])->assertForbidden();
        $this->actingAs($jefatura)->deleteJson("/api/sectores/{$rrhh->id}")->assertForbidden();
        $this->assertDatabaseHas('sectores', ['id' => $rrhh->id, 'nombre' => 'RRHH']);
    }

    public function test_sector_archivado_no_se_renombra_ni_se_saca(): void
    {
        $archivado = Sector::factory()->archivado()->create();
        $admin = $this->administrador();

        $this->actingAs($admin)->patchJson("/api/sectores/{$archivado->id}", ['nombre' => 'Otro'])->assertNotFound();
        $this->actingAs($admin)->deleteJson("/api/sectores/{$archivado->id}")->assertNotFound();
    }
}
