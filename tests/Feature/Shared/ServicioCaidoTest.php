<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use PDOException;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Psr\Log\LoggerInterface;
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
}
