<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\PedirCodigoAction;
use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\IdentidadServiceProvider;
use App\Modules\Identidad\Infrastructure\Mail\CodigoIngresoMail;
use Illuminate\Support\Facades\Mail;

/** F-01 · W-16 · HU-001/HU-002 (ADR-015, RN-IDE-02, 03, 06, 08): pedir el código por mail. */
final class PedirCodigoTest extends IdentidadTestCase
{
    public function test_pedir_codigo_manda_un_codigo_de_6_digitos_por_la_cola_de_mail(): void
    {
        $this->usuarioDe('Compras', ['nombre' => 'Carla', 'email' => 'carla@empresa-ejemplo.com']);
        Mail::fake();

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PedirCodigoAction::RESPUESTA, 'errors' => null]);

        Mail::assertQueued(CodigoIngresoMail::class, fn (CodigoIngresoMail $mail): bool => $mail->hasTo('carla@empresa-ejemplo.com')
            && $mail->queue === 'mail'
            && $mail->nombre === 'Carla'
            && $mail->minutos === CodigoIngreso::VIGENCIA_MINUTOS
            && preg_match('/^\d{6}$/', $mail->codigo) === 1);
    }

    public function test_codigo_se_guarda_hasheado_y_nunca_en_claro(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);

        $codigo = $this->pedirCodigo('carla@empresa-ejemplo.com');

        $this->assertNotNull($codigo);
        $guardado = CodigoIngreso::query()->where('user_id', $carla->id)->firstOrFail();
        $this->assertNotSame($codigo, $guardado->codigo_hash);
        $this->assertStringNotContainsString($codigo, $guardado->codigo_hash);
        $this->assertSame(64, strlen($guardado->codigo_hash), 'HMAC-SHA256 en hexadecimal');
        $this->assertArrayNotHasKey('codigo_hash', $guardado->toArray());
    }

    public function test_pedir_codigo_responde_igual_si_el_mail_no_existe(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        Mail::fake();

        $existe = $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();
        $noExiste = $this->postJson('/api/auth/pedir-codigo', ['email' => 'nadie@empresa-ejemplo.com'])->assertOk();

        $this->assertSame($existe->json(), $noExiste->json());
        Mail::assertQueuedCount(1);
        $this->assertSame(1, CodigoIngreso::query()->count());
    }

    public function test_usuario_inactivo_no_recibe_codigo(): void
    {
        User::factory()->enSector('Compras')->inactivo()->create(['email' => 'hugo@empresa-ejemplo.com']);
        Mail::fake();

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'hugo@empresa-ejemplo.com'])
            ->assertOk()
            ->assertJsonPath('message', PedirCodigoAction::RESPUESTA);

        Mail::assertNothingQueued();
        $this->assertSame(0, CodigoIngreso::query()->count());
    }

    public function test_pedir_codigo_compara_el_mail_sin_mayusculas_ni_espacios(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);

        $this->assertNotNull($this->pedirCodigo(' CARLA@Empresa-Ejemplo.com '));
    }

    public function test_pedir_codigo_rechaza_un_mail_sin_formato(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla-sin-arroba'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'El mail no es válido.');
        $this->postJson('/api/auth/pedir-codigo', [])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        Mail::assertNothingQueued();
    }

    public function test_pedir_codigo_nuevo_reemplaza_al_anterior(): void
    {
        $carla = $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);

        $this->pedirCodigo('carla@empresa-ejemplo.com');
        $this->pedirCodigo('carla@empresa-ejemplo.com');

        $this->assertSame(1, CodigoIngreso::query()->where('user_id', $carla->id)->count());
    }

    public function test_cuarto_pedido_del_mismo_mail_en_10_minutos_devuelve_429(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        Mail::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();
        }

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])
            ->assertTooManyRequests()
            ->assertExactJson(['data' => null, 'message' => IdentidadServiceProvider::DEMASIADOS_CODIGOS, 'errors' => null]);
        Mail::assertQueuedCount(3);

        $this->travel(11)->minutes();
        $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();
    }

    public function test_pedidos_de_codigo_desde_la_misma_ip_tienen_limite_por_hora(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/auth/pedir-codigo', ['email' => "persona{$i}@empresa-ejemplo.com"])->assertOk();
        }

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'otra@empresa-ejemplo.com'])->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/auth/pedir-codigo', ['email' => 'otra@empresa-ejemplo.com'])->assertOk();
    }

    public function test_el_alta_de_un_usuario_no_manda_mail_y_entra_pidiendo_un_codigo(): void
    {
        Mail::fake();

        $this->actingAs($this->administrador())->postJson('/api/usuarios', $this->datosDeAlta())
            ->assertCreated()
            ->assertJsonPath('message', 'Listo: Pablo ya puede entrar con su mail.');
        Mail::assertNothingQueued();
        $this->app['auth']->forgetGuards();

        $codigo = $this->pedirCodigo('pablo@empresa-ejemplo.com');
        $this->assertNotNull($codigo);
        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => 'pablo@empresa-ejemplo.com', 'codigo' => $codigo])
            ->assertOk()
            ->assertJsonPath('data.email', 'pablo@empresa-ejemplo.com');
    }
}
