<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\ElegirContrasenaAction;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\RecuperacionMail;
use App\Modules\Identidad\Infrastructure\TokensDeContrasena;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/** F-01 · W-16 · HU-002 (RN-IDE-06…08). */
final class RecuperacionTest extends IdentidadTestCase
{
    private const NUEVA = 'una-contrasena-nueva';

    public function test_olvide_contrasena_no_revela_si_el_mail_existe(): void
    {
        Mail::fake();
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        User::factory()->enSector('Compras')->inactivo()->create(['email' => 'diego@empresa-ejemplo.com']);

        $existe = $this->postJson('/api/auth/olvide-contrasena', ['email' => 'carla@empresa-ejemplo.com']);
        $noExiste = $this->postJson('/api/auth/olvide-contrasena', ['email' => 'nadie@empresa-ejemplo.com']);
        $inactivo = $this->postJson('/api/auth/olvide-contrasena', ['email' => 'diego@empresa-ejemplo.com']);

        $existe->assertOk()->assertJsonPath('message', 'Te mandamos un mail a carla@empresa-ejemplo.com para elegir una nueva.');
        $noExiste->assertOk()->assertJsonPath('message', 'Te mandamos un mail a nadie@empresa-ejemplo.com para elegir una nueva.');
        $inactivo->assertOk();
        Mail::assertQueuedCount(1);
        Mail::assertQueued(RecuperacionMail::class, fn (RecuperacionMail $mail): bool => $mail->hasTo('carla@empresa-ejemplo.com') && $mail->queue === 'mail');
    }

    public function test_olvide_contrasena_valida_el_formato_del_mail(): void
    {
        $this->postJson('/api/auth/olvide-contrasena', ['email' => 'no-es-un-mail'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cuarto_pedido_de_recuperacion_en_una_hora_devuelve_429(): void
    {
        Mail::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/olvide-contrasena', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();
        }

        $this->postJson('/api/auth/olvide-contrasena', ['email' => 'carla@empresa-ejemplo.com'])->assertTooManyRequests();
    }

    public function test_link_de_recuperacion_vence_a_los_60_minutos_y_sirve_una_vez(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $tokens = $this->app->make(TokensDeContrasena::class);

        $vencido = $tokens->paraRecuperacion($carla);
        $this->travel(61)->minutes();
        $this->elegir('carla@empresa-ejemplo.com', $vencido)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', ElegirContrasenaAction::LINK_VENCIDO);

        $vigente = $tokens->paraRecuperacion($carla);
        $this->elegir('carla@empresa-ejemplo.com', $vigente)->assertOk()->assertJsonPath('data.id', $carla->id);
        $this->assertTrue(Hash::check(self::NUEVA, (string) $carla->fresh()?->password));

        $this->elegir('carla@empresa-ejemplo.com', $vigente)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', ElegirContrasenaAction::LINK_VENCIDO);
    }

    public function test_con_la_contrasena_nueva_entra_y_la_anterior_deja_de_servir(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $token = $this->app->make(TokensDeContrasena::class)->paraRecuperacion($carla);

        $this->elegir('carla@empresa-ejemplo.com', $token)->assertOk();
        Auth::guard('web')->logout();

        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => 'carla@empresa-ejemplo.com', 'password' => UserFactory::PASSWORD])
            ->assertUnprocessable();
        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => 'carla@empresa-ejemplo.com', 'password' => self::NUEVA])
            ->assertOk();
    }

    public function test_elegir_contrasena_exige_10_caracteres_y_repetirla(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $token = $this->app->make(TokensDeContrasena::class)->paraRecuperacion($carla);

        $this->postJson('/api/auth/elegir-contrasena', [
            'token' => $token,
            'email' => 'carla@empresa-ejemplo.com',
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_elegir_contrasena_cierra_las_otras_sesiones(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        $hashViejo = Auth::guard('web')->hashPasswordForCookie((string) $carla->password);
        $token = $this->app->make(TokensDeContrasena::class)->paraRecuperacion($carla);

        $this->elegir('carla@empresa-ejemplo.com', $token)->assertOk();
        $this->assertAuthenticatedAs($carla, 'web');

        $carla = $carla->fresh() ?? $carla;
        $this->app['auth']->forgetGuards();

        // Otra sesión abierta con la contraseña anterior: en su próximo pedido queda afuera.
        $this->desdeElFront()->withSession(['password_hash_web' => $hashViejo])->actingAs($carla, 'web')
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    /**
     * @return TestResponse<Response>
     */
    private function elegir(string $email, string $token): TestResponse
    {
        return $this->desdeElFront()->postJson('/api/auth/elegir-contrasena', [
            'token' => $token,
            'email' => $email,
            'password' => self::NUEVA,
            'password_confirmation' => self::NUEVA,
        ]);
    }
}
