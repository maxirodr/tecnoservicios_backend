<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Idempotencia;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Idempotencia\IdempotencyKey;
use App\Shared\Idempotencia\Idempotent;
use App\Shared\Idempotencia\RequestFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * ADR-011: el doble envío con la misma Idempotency-Key devuelve la misma respuesta sin re-ejecutar.
 */
final class IdempotentMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private const RUTA = '/api/_pruebas/pedidos';

    private int $ejecuciones = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $middleware = ['api', 'auth:sanctum', 'idempotent'];

        Route::post(self::RUTA, function (Request $request): JsonResponse {
            $this->ejecuciones++;

            return ApiResponse::created(['numero' => $this->ejecuciones, 'tarea' => $request->input('tarea')]);
        })->middleware($middleware);

        Route::post('/api/_pruebas/otra', fn (): JsonResponse => ApiResponse::created(['otra' => true]))->middleware($middleware);

        Route::post('/api/_pruebas/valida', function (Request $request): JsonResponse {
            $this->ejecuciones++;

            return $request->filled('tarea')
                ? ApiResponse::created(['tarea' => $request->input('tarea')])
                : ApiResponse::error('Los datos enviados no son válidos.', 422, ['tarea' => ['Falta la tarea.']]);
        })->middleware($middleware);

        Route::post('/api/_pruebas/explota', function (): never {
            $this->ejecuciones++;

            throw new RuntimeException('falla interna');
        })->middleware($middleware);
    }

    public function test_doble_post_con_la_misma_clave_devuelve_la_misma_respuesta_sin_reejecutar(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $primera = $this->enviar($user, $clave, ['tarea' => 'Comprar cable'])->assertCreated();
        $segunda = $this->enviar($user, $clave, ['tarea' => 'Comprar cable'])->assertCreated();

        $this->assertSame(1, $this->ejecuciones);
        $this->assertSame($primera->json(), $segunda->json());
        $segunda->assertHeader(Idempotent::REPLAYED_HEADER, 'true');
        $this->assertStringContainsString('application/json', (string) $segunda->headers->get('Content-Type'));
        $this->assertSame(IdempotencyKey::COMPLETADA, IdempotencyKey::query()->sole()->status);
    }

    public function test_sin_clave_cada_envio_se_procesa(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(self::RUTA, ['tarea' => 'x'])->assertCreated();
        $this->actingAs($user)->postJson(self::RUTA, ['tarea' => 'x'])->assertCreated();

        $this->assertSame(2, $this->ejecuciones);
        $this->assertSame(0, IdempotencyKey::query()->count());
    }

    public function test_clave_que_no_es_uuid_es_rechazada(): void
    {
        $this->enviar(User::factory()->create(), 'doble-clic-1', ['tarea' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.idempotency_key.0', 'La Idempotency-Key tiene que ser un UUID.');

        $this->assertSame(0, $this->ejecuciones);
    }

    public function test_misma_clave_con_otro_payload_devuelve_422(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, ['tarea' => 'Comprar cable'])->assertCreated();
        $this->enviar($user, $clave, ['tarea' => 'Otra cosa'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.idempotency_key.0', 'Esta Idempotency-Key ya se usó con otros datos.');

        $this->assertSame(1, $this->ejecuciones);
    }

    public function test_misma_clave_en_otra_ruta_devuelve_422(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, [])->assertCreated();
        $this->enviar($user, $clave, [], '/api/_pruebas/otra')->assertUnprocessable();
    }

    public function test_el_orden_de_los_campos_no_cambia_el_payload(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, ['tarea' => 'x', 'obra_id' => 1])->assertCreated();
        $this->enviar($user, $clave, ['obra_id' => 1, 'tarea' => 'x'])->assertCreated();

        $this->assertSame(1, $this->ejecuciones);
    }

    public function test_misma_clave_de_otro_usuario_es_independiente(): void
    {
        $clave = (string) Str::uuid();

        $this->enviar(User::factory()->create(), $clave, ['tarea' => 'x'])->assertCreated()->assertJsonPath('data.numero', 1);
        $this->enviar(User::factory()->create(), $clave, ['tarea' => 'x'])->assertCreated()->assertJsonPath('data.numero', 2);

        $this->assertSame(2, IdempotencyKey::query()->count());
    }

    public function test_request_en_curso_con_la_misma_clave_devuelve_409(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();
        $this->reservaEnCurso($user, $clave);

        $this->enviar($user, $clave, ['tarea' => 'x'])
            ->assertConflict()
            ->assertHeader('Retry-After', '1')
            ->assertJsonPath('data', null);

        $this->assertSame(0, $this->ejecuciones);
    }

    public function test_en_curso_abandonado_se_retoma(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();
        $this->travelTo(now()->subMinutes(2));
        $this->reservaEnCurso($user, $clave);
        $this->travelBack();

        $this->enviar($user, $clave, ['tarea' => 'x'])->assertCreated();

        $this->assertSame(1, $this->ejecuciones);
    }

    public function test_respuesta_con_error_libera_la_clave_para_reintentar(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, [], '/api/_pruebas/valida')->assertUnprocessable();
        $this->enviar($user, $clave, ['tarea' => 'corregida'], '/api/_pruebas/valida')->assertCreated();

        $this->assertSame(2, $this->ejecuciones);
    }

    public function test_error_de_servidor_libera_la_clave(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, [], '/api/_pruebas/explota')->assertServerError();
        $this->enviar($user, $clave, [], '/api/_pruebas/explota')->assertServerError();

        $this->assertSame(2, $this->ejecuciones);
        $this->assertSame(0, IdempotencyKey::query()->count());
    }

    public function test_clave_vencida_a_las_24_horas_se_procesa_de_nuevo(): void
    {
        $user = User::factory()->create();
        $clave = (string) Str::uuid();

        $this->enviar($user, $clave, ['tarea' => 'x'])->assertCreated();
        $this->travel(IdempotencyKey::HORAS_VIGENCIA)->hours();
        $this->enviar($user, $clave, ['tarea' => 'x'])->assertCreated()->assertHeaderMissing(Idempotent::REPLAYED_HEADER);

        $this->assertSame(2, $this->ejecuciones);
    }

    public function test_purga_borra_las_claves_de_mas_de_24_horas(): void
    {
        $user = User::factory()->create();
        $this->enviar($user, (string) Str::uuid(), ['tarea' => 'vieja'])->assertCreated();
        $this->travel(IdempotencyKey::HORAS_VIGENCIA)->hours();
        $this->enviar($user, (string) Str::uuid(), ['tarea' => 'nueva'])->assertCreated();

        $this->command('idempotencia:purgar')->expectsOutputToContain('purgadas: 1')->assertSuccessful();

        $this->assertSame(1, IdempotencyKey::query()->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return TestResponse<Response>
     */
    private function enviar(User $user, string $clave, array $payload, string $ruta = self::RUTA): TestResponse
    {
        return $this->actingAs($user)->postJson($ruta, $payload, [Idempotent::HEADER => $clave]);
    }

    private function reservaEnCurso(User $user, string $clave): void
    {
        IdempotencyKey::query()->create([
            'user_id' => $user->id,
            'clave' => $clave,
            'ruta' => 'POST '.ltrim(self::RUTA, '/'),
            'payload_hash' => (new RequestFingerprint)->hash(Request::create(self::RUTA, 'POST', ['tarea' => 'x'])),
            'status' => IdempotencyKey::EN_CURSO,
        ]);
    }
}
