<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Http\Middleware\LimiteDelHealth;
use App\Shared\Providers\SharedServiceProvider;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Tests\TestCase;

/** GET /api/health: rápido con Redis caído (PU-020) y atento a la cola de mails (PU-021). */
final class SaludTest extends TestCase
{
    private const SEGUNDOS_MAXIMOS = 3;

    private string $cola;

    private bool $encolado = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cola = 'salud-test-'.Str::random(8);
        config(['salud.colas' => [$this->cola]]);
    }

    protected function tearDown(): void
    {
        if ($this->encolado) {
            Queue::connection('redis')->clear($this->cola);
        }

        parent::tearDown();
    }

    public function test_con_redis_caido_responde_503_rapido_y_sin_pasar_por_el_limitador_en_redis(): void
    {
        config([
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'database.redis.default.port' => 1,
            'database.redis.cache.port' => 1,
        ]);
        // El RedisManager copia la config al crearse: se lo descarta para que use el puerto cerrado.
        $this->app->forgetInstance('redis');
        Redis::clearResolvedInstance('redis');
        $this->app['cache']->forgetDriver('redis');

        $inicio = microtime(true);
        $respuesta = $this->get('/api/health');
        $segundos = microtime(true) - $inicio;

        $respuesta->assertStatus(503)
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.services.redis', false)
            ->assertJsonPath('data.services.cache', false)
            ->assertJsonPath('data.services.cola_mail', false)
            ->assertJsonPath('data.services.database', true);
        $this->assertLessThan(self::SEGUNDOS_MAXIMOS, $segundos, "El health tardó {$segundos} s");
    }

    public function test_el_health_no_pasa_por_el_limitador_general_ni_por_la_sesion_que_viven_en_redis(): void
    {
        $route = Route::getRoutes()->getByName('health');
        $this->assertNotNull($route);

        $middleware = $this->app->make(Router::class)->gatherRouteMiddleware($route);

        $this->assertNotContains(ThrottleRequests::class.':'.SharedServiceProvider::LIMITE_API, $middleware);
        $this->assertNotContains(EnsureFrontendRequestsAreStateful::class, $middleware);
        $this->assertContains(LimiteDelHealth::class, $middleware);
    }

    public function test_job_de_mail_sin_levantar_hace_mas_de_lo_permitido_marca_la_cola_caida(): void
    {
        config(['queue.default' => 'redis', 'salud.cola_max_segundos' => 120]);
        $this->encolar(now()->subSeconds(300)->getTimestamp());

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('data.status', 'degraded')
            ->assertJsonPath('data.services.cola_mail', false)
            ->assertJsonPath('data.services.redis', true);
    }

    public function test_job_reciente_en_la_cola_no_es_problema(): void
    {
        config(['queue.default' => 'redis', 'salud.cola_max_segundos' => 120]);
        $this->encolar(now()->subSeconds(10)->getTimestamp());

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.services.cola_mail', true);
    }

    public function test_el_health_tiene_su_propio_limite_por_ip_fuera_del_limite_general(): void
    {
        config(['app.api_requests_per_minute' => 1, 'salud.pedidos_por_minuto' => 2]);

        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/health')->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('data', null);
    }

    private function encolar(int $creadoEn): void
    {
        $this->encolado = true;
        Queue::connection('redis')->pushRaw((string) json_encode(['uuid' => (string) Str::uuid(), 'createdAt' => $creadoEn]), $this->cola);
    }
}
