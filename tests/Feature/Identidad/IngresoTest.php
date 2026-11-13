<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\IngresarAction;
use App\Modules\Identidad\Domain\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;

/** F-01 · W-16 · HU-001 (RN-IDE-01…05). */
final class IngresoTest extends IdentidadTestCase
{
    public function test_ingresa_con_mail_de_la_empresa_y_contrasena(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura', 'email' => 'laura@empresa-ejemplo.com']);

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'laura@empresa-ejemplo.com',
            'password' => UserFactory::PASSWORD,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $laura->id)
            ->assertJsonPath('data.sector.nombre', 'Ingeniería de proyectos')
            ->assertJsonPath('errors', null);

        $this->assertAuthenticatedAs($laura, 'web');
    }

    public function test_mail_se_compara_sin_mayusculas_ni_espacios(): void
    {
        $laura = $this->usuarioDe('Compras', ['email' => 'laura@empresa-ejemplo.com']);

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => ' LAURA@empresa-ejemplo.com ',
            'password' => UserFactory::PASSWORD,
        ])->assertOk()->assertJsonPath('data.id', $laura->id);
    }

    public function test_credenciales_incorrectas_dan_el_mismo_mensaje(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);

        $contrasenaMal = $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'carla@empresa-ejemplo.com',
            'password' => 'otra-contrasena-cualquiera',
        ]);
        $mailInexistente = $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'nadie@empresa-ejemplo.com',
            'password' => UserFactory::PASSWORD,
        ]);

        $contrasenaMal->assertUnprocessable()->assertJsonPath('errors.email.0', IngresarAction::CREDENCIALES_INCORRECTAS);
        $mailInexistente->assertUnprocessable()->assertJsonPath('errors.email.0', IngresarAction::CREDENCIALES_INCORRECTAS);
        $this->assertGuest('web');
    }

    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        User::factory()->enSector('Compras')->inactivo()->create(['email' => 'carla@empresa-ejemplo.com']);

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'carla@empresa-ejemplo.com',
            'password' => UserFactory::PASSWORD,
        ])->assertUnprocessable()->assertJsonPath('errors.email.0', IngresarAction::USUARIO_INACTIVO);

        $this->assertGuest('web');
    }

    public function test_usuario_que_no_eligio_contrasena_no_puede_ingresar(): void
    {
        User::factory()->enSector('Compras')->sinContrasena()->create(['email' => 'pablo@empresa-ejemplo.com']);

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'pablo@empresa-ejemplo.com',
            'password' => '',
        ])->assertUnprocessable();

        $this->assertGuest('web');
    }

    public function test_sexto_intento_en_un_minuto_devuelve_429(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $intento = ['email' => 'carla@empresa-ejemplo.com', 'password' => 'no-es-la-contrasena'];

        for ($i = 0; $i < 5; $i++) {
            $this->desdeElFront()->postJson('/api/auth/ingresar', $intento)->assertUnprocessable();
        }

        $this->desdeElFront()->postJson('/api/auth/ingresar', $intento)
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Demasiados intentos. Probá de nuevo en un minuto.')
            ->assertJsonPath('data', null);
    }

    public function test_recordarme_deja_cookie_remember(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $cookie = Auth::guard('web')->getRecallerName();

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'carla@empresa-ejemplo.com',
            'password' => UserFactory::PASSWORD,
            'recordarme' => true,
        ])->assertOk()->assertCookie($cookie);
    }

    public function test_sin_recordarme_no_deja_cookie_remember(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);

        $this->desdeElFront()->postJson('/api/auth/ingresar', [
            'email' => 'carla@empresa-ejemplo.com',
            'password' => UserFactory::PASSWORD,
        ])->assertOk()->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    public function test_salir_cierra_la_sesion(): void
    {
        $carla = $this->usuarioDe('Compras');

        $this->actingAs($carla)->postJson('/api/auth/salir')->assertOk()->assertJsonPath('data', null);

        $this->assertGuest('web');
    }

    public function test_salir_sin_sesion_devuelve_401(): void
    {
        $this->postJson('/api/auth/salir')->assertUnauthorized()->assertJsonStructure(['data', 'message', 'errors']);
    }

    public function test_usuario_desactivado_pierde_su_sesion_en_el_proximo_pedido(): void
    {
        $carla = $this->usuarioDe('Compras');
        $carla->forceFill(['activo' => false])->save();

        $this->actingAs($carla)->getJson('/api/me')->assertUnauthorized();
        $this->assertGuest('web');
    }
}
