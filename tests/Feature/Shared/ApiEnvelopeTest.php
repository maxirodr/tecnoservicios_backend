<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class ApiEnvelopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/_envelope')->group(function (): void {
            Route::get('ok', fn () => ApiResponse::success(['id' => 1], 'Listo'));
            Route::get('paginated', fn () => ApiResponse::success(
                new LengthAwarePaginator([['id' => 1], ['id' => 2]], 5, 2, 1),
            ));
            Route::get('unauthenticated', fn () => throw new AuthenticationException);
            Route::get('forbidden', fn () => throw new AuthorizationException('secreto interno'));
            Route::get('model-missing', fn () => throw new ModelNotFoundException);
            Route::post('validate', fn (Request $request) => $request->validate([
                'nombre' => ['required', 'string'],
                'fecha' => ['required', 'date'],
            ]));
            Route::get('boom', fn () => throw new RuntimeException('SQLSTATE detalle interno'));
            Route::get('throttled', fn () => 'ok')->middleware('throttle:1,1');
        });
    }

    #[Test]
    public function success_responses_wrap_payload_in_data_with_message_and_null_errors(): void
    {
        $this->getJson('/api/_envelope/ok')
            ->assertOk()
            ->assertExactJson(['data' => ['id' => 1], 'message' => 'Listo', 'errors' => null]);
    }

    #[Test]
    public function paginated_lists_carry_meta_and_links_inside_the_envelope(): void
    {
        $this->getJson('/api/_envelope/paginated')
            ->assertOk()
            ->assertJsonPath('data', [['id' => 1], ['id' => 2]])
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('errors', null)
            ->assertJsonStructure(['data', 'message', 'errors', 'meta', 'links' => ['prev', 'next', 'first', 'last']]);
    }

    #[Test]
    public function unauthenticated_requests_get_401_in_envelope(): void
    {
        $this->getJson('/api/_envelope/unauthenticated')
            ->assertUnauthorized()
            ->assertExactJson(['data' => null, 'message' => 'Tenés que ingresar para seguir.', 'errors' => null]);
    }

    #[Test]
    public function forbidden_requests_get_403_in_envelope_without_internal_message(): void
    {
        $this->getJson('/api/_envelope/forbidden')
            ->assertForbidden()
            ->assertExactJson(['data' => null, 'message' => 'No tenés permiso para hacer esto.', 'errors' => null]);
    }

    #[Test]
    public function unknown_routes_and_missing_models_get_404_in_envelope(): void
    {
        $this->getJson('/api/no-existe')
            ->assertNotFound()
            ->assertExactJson(['data' => null, 'message' => 'No encontramos lo que buscabas.', 'errors' => null]);

        $this->getJson('/api/_envelope/model-missing')
            ->assertNotFound()
            ->assertExactJson(['data' => null, 'message' => 'No encontramos lo que buscabas.', 'errors' => null]);
    }

    #[Test]
    public function unknown_routes_outside_api_also_get_json_envelope_not_html(): void
    {
        $this->get('/no-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'No encontramos lo que buscabas.');
    }

    #[Test]
    public function wrong_http_method_gets_405_in_envelope(): void
    {
        $this->deleteJson('/api/_envelope/ok')
            ->assertStatus(405)
            ->assertExactJson(['data' => null, 'message' => 'Esa acción no está disponible.', 'errors' => null]);
    }

    #[Test]
    public function validation_failures_get_422_with_errors_keyed_by_field(): void
    {
        $response = $this->postJson('/api/_envelope/validate', ['fecha' => 'no-es-fecha'])
            ->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Los datos enviados no son válidos.');

        $this->assertSame(['nombre', 'fecha'], array_keys($response->json('errors')));
        $this->assertIsString($response->json('errors.nombre.0'));
    }

    #[Test]
    public function exceeding_rate_limit_gets_429_in_envelope_with_retry_after(): void
    {
        $this->getJson('/api/_envelope/throttled')->assertOk();

        $this->getJson('/api/_envelope/throttled')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertExactJson(['data' => null, 'message' => 'Fueron demasiadas acciones seguidas. Esperá unos segundos y volvé a intentar.', 'errors' => null]);
    }

    #[Test]
    public function server_errors_in_production_hide_exception_details(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_envelope/boom')
            ->assertStatus(500)
            ->assertExactJson(['data' => null, 'message' => 'El sistema tuvo un problema. Volvé a intentar en un momento.', 'errors' => null]);

        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', (string) $response->getContent());
    }

    #[Test]
    public function server_errors_in_debug_expose_details_only_under_errors_debug(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/api/_envelope/boom')
            ->assertStatus(500)
            ->assertJsonPath('message', 'El sistema tuvo un problema. Volvé a intentar en un momento.')
            ->assertJsonPath('errors.debug.exception', RuntimeException::class);
    }
}
