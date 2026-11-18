<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use PDO;
use PDOException;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\TestCase;

/** PU-138: sin base o sin Redis el sistema responde 503 "no está disponible", no 500. */
final class ServicioCaidoTest extends TestCase
{
    use RefreshDatabase;

    private const NO_DISPONIBLE = 'El sistema no está disponible en este momento. Probá de nuevo en unos minutos.';

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/_caido')->group(function (): void {
            Route::get('mysql', fn () => throw new QueryException('mysql', 'select 1', [], new PDOException('SQLSTATE[HY000] [2002] Connection refused', 2002)));
            Route::get('mysql-se-fue', fn () => throw new QueryException('mysql', 'select 1', [], new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away')));
            Route::get('predis', fn () => throw new StreamInitException('Connection refused [tcp://127.0.0.1:6379]'));
            Route::get('predis-comunicacion', fn () => throw new ConnectionException($this->createStub(NodeConnectionInterface::class), 'Connection reset by peer'));
            Route::get('sql-roto', fn () => throw new QueryException('mysql', 'select * from no_existe', [], new PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'no_existe' doesn't exist")));
            Route::middleware('auth:sanctum')->get('con-sesion', fn () => throw new RuntimeException('falla propia'));
            Route::get('duplicado', fn () => throw new QueryException('mysql', 'insert', [], new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'x'")));
        });
    }

    public function test_falla_de_conexion_con_mysql_o_redis_da_503_con_retry_after(): void
    {
        foreach (['mysql', 'mysql-se-fue', 'predis', 'predis-comunicacion'] as $caso) {
            $this->getJson("/api/_caido/{$caso}")
                ->assertStatus(503)
                ->assertHeader('Retry-After')
                ->assertExactJson(['data' => null, 'message' => self::NO_DISPONIBLE, 'errors' => null]);
        }
    }

    public function test_otros_errores_de_la_base_siguen_siendo_500(): void
    {
        foreach (['sql-roto', 'duplicado'] as $caso) {
            $this->getJson("/api/_caido/{$caso}")->assertStatus(500)->assertHeaderMissing('Retry-After');
        }
    }

    public function test_el_503_por_servicio_caido_queda_en_el_canal_errors(): void
    {
        $canal = Mockery::spy(LoggerInterface::class);
        Log::shouldReceive('channel')->with('errors')->andReturn($canal);

        $this->getJson('/api/_caido/mysql')->assertStatus(503);

        $canal->shouldHaveReceived('error')->once();
    }

    public function test_me_con_la_base_caida_responde_503(): void
    {
        $user = User::factory()->create();
        $default = config('database.default');
        config([
            'database.connections.caida' => [...(array) config('database.connections.mysql'), 'port' => 1],
            'database.default' => 'caida',
        ]);

        try {
            $this->actingAs($user)->getJson('/api/me')
                ->assertStatus(503)
                ->assertJsonPath('message', self::NO_DISPONIBLE);
        } finally {
            // La transacción del test vive en la conexión de siempre: se la devuelve para deshacerla.
            config(['database.default' => $default]);
        }
    }

    public function test_con_la_base_caida_y_sesion_por_cookie_da_503_rapido_y_queda_en_el_log(): void
    {
        // PU-202: con la sesión (no `actingAs`), el usuario se carga de la base caída. El reporte del error
        // no puede volver a consultarla, y la conexión no se reintenta: antes eran 500 en más de 30 s.
        $user = User::factory()->create();
        $canal = Mockery::spy(LoggerInterface::class);
        Log::shouldReceive('channel')->with('errors')->andReturn($canal);
        $default = config('database.default');
        config([
            'database.connections.caida' => [
                ...(array) config('database.connections.mysql'),
                'port' => 1,
                'options' => [PDO::ATTR_TIMEOUT => 1],
            ],
            'database.default' => 'caida',
        ]);

        try {
            $inicio = microtime(true);
            $this->withSession([Auth::guard()->getName() => $user->getKey()])
                ->getJson('/api/me')
                ->assertStatus(503)
                ->assertHeader('Retry-After')
                ->assertJsonPath('message', self::NO_DISPONIBLE);
            $segundos = microtime(true) - $inicio;
        } finally {
            config(['database.default' => $default]);
        }

        // Un solo intento de conexión de hasta 1 s (sin el arreglo: 4 por consulta y una consulta más en el log).
        $this->assertLessThan(2.5, $segundos);
        $canal->shouldHaveReceived('error')->once()->withArgs(
            fn (string $mensaje, array $contexto): bool => $contexto['user_id'] === $user->getKey()
                && $contexto['exception_class'] === QueryException::class,
        );
    }

    public function test_el_log_de_un_error_con_sesion_lleva_el_usuario_sin_volver_a_consultarlo(): void
    {
        // PU-202: después de `auth:sanctum` el guard en uso es `sanctum`; el log toma el usuario ya cargado.
        $user = User::factory()->create();
        $canal = Mockery::spy(LoggerInterface::class);
        Log::shouldReceive('channel')->with('errors')->andReturn($canal);
        $consultas = 0;

        $this->withSession([Auth::guard('web')->getName() => $user->getKey()]);
        DB::listen(function () use (&$consultas): void {
            $consultas++;
        });

        $this->getJson('/api/_caido/con-sesion')->assertStatus(500);

        $this->assertSame(1, $consultas, 'Solo la del usuario al autenticar; el log no vuelve a consultar.');
        $canal->shouldHaveReceived('error')->once()->withArgs(
            fn (string $mensaje, array $contexto): bool => $contexto['user_id'] === $user->getKey(),
        );
    }
}
