<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Facades\Mail;

/** F-58 · W-39 · W-15 · HU-005 (RN-IDE-09, RN-IDE-19, RN-IDE-26). */
final class UsuariosAltaTest extends IdentidadTestCase
{
    public function test_solo_usuarios_permisos_editar_da_de_alta_usuarios(): void
    {
        Mail::fake();
        $jefaturaAdministracion = $this->usuarioDe('Jefatura de Administración');
        $calidad = $this->usuarioDe('Calidad');

        $this->actingAs($jefaturaAdministracion)->postJson('/api/usuarios', $this->datosDeAlta())->assertForbidden();
        $this->actingAs($calidad)->postJson('/api/usuarios', $this->datosDeAlta())->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'pablo@empresa-ejemplo.com']);

        $admin = $this->administrador();
        $this->actingAs($admin)->postJson('/api/usuarios', $this->datosDeAlta())
            ->assertCreated()
            ->assertJsonPath('data.activo', true)
            ->assertJsonMissingPath('data.eligio_contrasena')
            ->assertJsonPath('data.sector.nombre', 'Ingeniería de proyectos');
        $pablo = User::query()->where('email', 'pablo@empresa-ejemplo.com')->firstOrFail();
        $this->assertTrue($pablo->activo);

        $creado = $this->actividades(User::class, 'creado');
        $this->assertCount(1, $creado);
        $this->assertSame($pablo->id, $creado[0]->sujeto_id);
        $this->assertSame($admin->id, $creado[0]->user_id);
    }

    public function test_alta_de_usuario_no_manda_mail_de_bienvenida(): void
    {
        Mail::fake();

        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta())
            ->assertCreated()
            ->assertJsonPath('message', 'Listo: Pablo ya puede entrar con su mail.');

        Mail::assertNothingQueued();
        Mail::assertNothingSent();
    }

    public function test_mail_de_usuario_es_unico_incluso_entre_inactivos(): void
    {
        User::factory()->enSector('Compras')->inactivo()->create(['nombre' => 'Diego', 'email' => 'diego@empresa-ejemplo.com']);

        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta(['email' => 'DIEGO@empresa-ejemplo.com']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Ese mail ya es de otro usuario (Diego, inactivo). Podés reactivarlo.');
    }

    public function test_alta_pide_nombre_mail_y_sector(): void
    {
        $this->actingAs($this->administrador())->postJson('/api/usuarios', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'email', 'sector_id']);
    }

    public function test_alta_no_acepta_un_sector_archivado(): void
    {
        $archivado = Sector::factory()->archivado()->create();

        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta(['sector_id' => $archivado->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sector_id']);
    }

    public function test_alta_no_acepta_jefe_inactivo(): void
    {
        $inactivo = User::factory()->enSector('Compras')->inactivo()->create();

        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta(['jefe_id' => $inactivo->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jefe_id']);
    }

    public function test_alta_respeta_dominios_permitidos_si_se_configuran(): void
    {
        Mail::fake();
        config(['identidad.dominios_permitidos' => ['empresa-ejemplo.com']]);
        $admin = $this->administrador();

        $this->actingAs($admin)->postJson('/api/usuarios', $this->datosDeAlta(['email' => 'pablo@otro-dominio.com']))
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
        $this->actingAs($admin)->postJson('/api/usuarios', $this->datosDeAlta())->assertCreated();
    }
}
