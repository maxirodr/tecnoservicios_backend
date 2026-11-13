<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\TextoEnEspanol;
use Tests\TestCase;

/**
 * Los errores del framework que pasan por ApiExceptionRenderer llegan en español, aunque la excepción
 * traiga un texto en inglés (`abort()` sin mensaje, "CSRF token mismatch.", "Not Found"…).
 */
final class ErroresEnEspanolTest extends TestCase
{
    use TextoEnEspanol;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/_errores')->group(function (): void {
            Route::get('ok', fn () => 'ok');
            Route::get('csrf', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
            Route::post('grande', fn () => throw new PostTooLargeException('The POST data is too large.'));
            Route::get('abort/{status}', fn (int $status) => abort($status));
            Route::get('roto', fn () => throw new RuntimeException('Something went wrong'));
            Route::get('limitado', fn () => 'ok')->middleware('throttle:1,1');
        });
    }

    public function test_ruta_inexistente_da_404_en_espanol(): void
    {
        $this->assertMensaje($this->getJson('/api/no-existe'), 404, 'No encontramos lo que buscabas.');
    }

    public function test_metodo_no_permitido_da_405_en_espanol(): void
    {
        $this->assertMensaje($this->deleteJson('/api/_errores/ok'), 405, 'Esa acción no está disponible.');
    }

    public function test_envio_demasiado_grande_da_413_en_espanol(): void
    {
        $this->assertMensaje(
            $this->postJson('/api/_errores/grande'),
            413,
            'Lo que enviaste es demasiado grande. Si es un archivo, probá con uno más liviano.',
        );
    }

    public function test_sesion_vencida_da_419_en_espanol(): void
    {
        $this->assertMensaje($this->getJson('/api/_errores/csrf'), 419, 'La sesión expiró. Volvé a ingresar.');
    }

    public function test_demasiadas_solicitudes_da_429_en_espanol(): void
    {
        $this->getJson('/api/_errores/limitado')->assertOk();

        $this->assertMensaje(
            $this->getJson('/api/_errores/limitado'),
            429,
            'Fueron demasiadas acciones seguidas. Esperá unos segundos y volvé a intentar.',
        );
    }

    public function test_error_interno_da_500_en_espanol(): void
    {
        config(['app.debug' => false]);

        $this->assertMensaje($this->getJson('/api/_errores/roto'), 500, 'El sistema tuvo un problema. Volvé a intentar en un momento.');
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function abortsSinMensaje(): array
    {
        return [
            'sin permiso' => [403, 'No tenés permiso para hacer esto.'],
            'no encontrado' => [404, 'No encontramos lo que buscabas.'],
            'sesión vencida' => [419, 'La sesión expiró. Volvé a ingresar.'],
            'en mantenimiento' => [503, 'El sistema no está disponible en este momento. Probá de nuevo en unos minutos.'],
        ];
    }

    #[DataProvider('abortsSinMensaje')]
    public function test_abort_sin_mensaje_responde_en_espanol(int $status, string $mensaje): void
    {
        $this->assertMensaje($this->getJson("/api/_errores/abort/{$status}"), $status, $mensaje);
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    private function assertMensaje(TestResponse $response, int $status, string $mensaje): void
    {
        $response->assertStatus($status)->assertExactJson(['data' => null, 'message' => $mensaje, 'errors' => null]);

        $this->assertRespuestaSinIngles($response);
    }
}
