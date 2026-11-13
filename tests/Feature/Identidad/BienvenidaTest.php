<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\ElegirContrasenaAction;
use App\Modules\Identidad\Application\Usuarios\ReenviarBienvenidaAction;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\BienvenidaMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/** F-01 · F-58 · HU-005 criterios 4 y 5 (RN-IDE-09): mail de bienvenida, link de 7 días y reenvío. */
final class BienvenidaTest extends IdentidadTestCase
{
    public function test_link_de_bienvenida_dura_7_dias(): void
    {
        $token = $this->altaYToken();

        $this->travel(7 * 24 * 60 - 1)->minutes();
        $this->elegir($token)->assertOk();
    }

    public function test_link_de_bienvenida_vencido_no_sirve(): void
    {
        $token = $this->altaYToken();

        $this->travel(7 * 24 * 60 + 1)->minutes();
        $this->elegir($token)->assertUnprocessable()->assertJsonPath('errors.token.0', ElegirContrasenaAction::LINK_VENCIDO);
    }

    public function test_reenviar_bienvenida_manda_link_nuevo_e_invalida_el_anterior(): void
    {
        $viejo = $this->altaYToken();
        $pablo = User::query()->where('email', 'pablo@empresa-ejemplo.com')->firstOrFail();
        Mail::fake();

        $this->actingAs($this->administrador())->postJson("/api/usuarios/{$pablo->id}/reenviar-bienvenida")
            ->assertOk()
            ->assertJsonPath('message', 'Le mandamos de nuevo el mail a pablo@empresa-ejemplo.com.');

        $nuevo = null;
        Mail::assertQueued(BienvenidaMail::class, function (BienvenidaMail $mail) use (&$nuevo): bool {
            $nuevo = $this->tokenDe($mail);

            return true;
        });

        $this->elegir($viejo)->assertUnprocessable();
        $this->elegir((string) $nuevo)->assertOk();
    }

    public function test_no_reenvia_bienvenida_a_quien_ya_eligio_contrasena(): void
    {
        $carla = $this->usuarioDe('Compras');
        $inactivo = User::factory()->enSector('Compras')->inactivo()->sinContrasena()->create();
        $admin = $this->administrador();

        $this->actingAs($admin)->postJson("/api/usuarios/{$carla->id}/reenviar-bienvenida")
            ->assertUnprocessable()
            ->assertJsonPath('errors.usuario.0', ReenviarBienvenidaAction::YA_ELIGIO_CONTRASENA);
        $this->actingAs($admin)->postJson("/api/usuarios/{$inactivo->id}/reenviar-bienvenida")
            ->assertUnprocessable()
            ->assertJsonPath('errors.usuario.0', ReenviarBienvenidaAction::USUARIO_INACTIVO);
    }

    public function test_sin_usuarios_permisos_editar_no_reenvia_bienvenida(): void
    {
        Mail::fake();
        $pablo = User::factory()->enSector('Compras')->sinContrasena()->create();

        $this->actingAs($this->usuarioDe('Dirección'))->postJson("/api/usuarios/{$pablo->id}/reenviar-bienvenida")
            ->assertOk();
        $this->actingAs($this->usuarioDe('Jefatura de Proyectos'))->postJson("/api/usuarios/{$pablo->id}/reenviar-bienvenida")
            ->assertForbidden();
        $this->actingAs($this->usuarioDe('Jefatura de Proyectos'))->postJson('/api/usuarios/999999/reenviar-bienvenida')
            ->assertForbidden();
        $this->actingAs($this->administrador())->postJson('/api/usuarios/999999/reenviar-bienvenida')
            ->assertNotFound();
    }

    public function test_cuarto_reenvio_de_bienvenida_en_una_hora_devuelve_429(): void
    {
        Mail::fake();
        $pablo = User::factory()->enSector('Compras')->sinContrasena()->create();
        $admin = $this->administrador();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->postJson("/api/usuarios/{$pablo->id}/reenviar-bienvenida")->assertOk();
        }

        $this->actingAs($admin)->postJson("/api/usuarios/{$pablo->id}/reenviar-bienvenida")->assertTooManyRequests();
    }

    private function altaYToken(): string
    {
        Mail::fake();
        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta())->assertCreated();

        $token = null;
        Mail::assertQueued(BienvenidaMail::class, function (BienvenidaMail $mail) use (&$token): bool {
            $token = $this->tokenDe($mail);

            return true;
        });
        $this->app['auth']->forgetGuards();

        return (string) $token;
    }

    private function tokenDe(BienvenidaMail $mail): string
    {
        parse_str((string) parse_url($mail->url, PHP_URL_QUERY), $query);

        return is_string($query['token'] ?? null) ? $query['token'] : '';
    }

    /**
     * @return TestResponse<Response>
     */
    private function elegir(string $token): TestResponse
    {
        return $this->postJson('/api/auth/elegir-contrasena', [
            'token' => $token,
            'email' => 'pablo@empresa-ejemplo.com',
            'password' => 'mi-primera-contrasena',
            'password_confirmation' => 'mi-primera-contrasena',
        ]);
    }
}
