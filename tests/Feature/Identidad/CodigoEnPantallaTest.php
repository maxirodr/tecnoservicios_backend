<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\PedirCodigoAction;
use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use Illuminate\Support\Facades\Mail;

/** Servidores de prueba (IDENTIDAD_CODIGO_EN_PANTALLA): el código se muestra en pantalla; nunca en producción. */
final class CodigoEnPantallaTest extends IdentidadTestCase
{
    public function test_en_servidor_de_prueba_el_codigo_se_muestra_y_no_se_manda_mail(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        config(['identidad.codigo_en_pantalla' => true]);
        Mail::fake();

        $respuesta = $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();

        $this->assertMatchesRegularExpression('/tu código es \d{6}/', (string) $respuesta->json('message'));
        Mail::assertNothingQueued();
        $this->assertSame(1, CodigoIngreso::query()->count());
    }

    public function test_en_servidor_de_prueba_un_mail_inexistente_recibe_la_respuesta_de_siempre(): void
    {
        config(['identidad.codigo_en_pantalla' => true]);

        $this->postJson('/api/auth/pedir-codigo', ['email' => 'nadie@empresa-ejemplo.com'])
            ->assertOk()
            ->assertJsonPath('message', PedirCodigoAction::RESPUESTA);
    }

    public function test_en_produccion_el_codigo_nunca_se_muestra_aunque_la_variable_este_prendida(): void
    {
        $this->usuarioDe('Compras', ['email' => 'carla@empresa-ejemplo.com']);
        config(['identidad.codigo_en_pantalla' => true]);
        $this->app->detectEnvironment(fn (): string => 'production');
        Mail::fake();

        $respuesta = $this->postJson('/api/auth/pedir-codigo', ['email' => 'carla@empresa-ejemplo.com'])->assertOk();

        $this->assertSame(PedirCodigoAction::RESPUESTA, $respuesta->json('message'));
        $this->assertDoesNotMatchRegularExpression('/\d{6}/', (string) $respuesta->getContent());
    }
}
