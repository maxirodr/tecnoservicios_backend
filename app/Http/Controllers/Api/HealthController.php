<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Support\SondaDeBase;
use App\Http\Controllers\Controller;
use App\Shared\Http\ApiResponse;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * GET /api/health — público. Solo booleanos por servicio: no expone versiones ni nombres del stack.
 * Responde rápido aunque Redis o MySQL estén caídos (timeouts cortos en config/database.php, PU-020)
 * y avisa si la cola de mails no avanza (PU-021): sin ese worker nadie recibe el código para entrar.
 * `redis` es null si ni el cache, ni la sesión, ni la cola lo usan (no aplica: no se lo consulta).
 * Un servicio caído se consulta una sola vez (PU-139): lo que vive en él da false sin volver a esperar.
 */
final class HealthController extends Controller
{
    private const CACHE = ['cache.default', 'cache.stores.%s.driver'];

    private const COLA = ['queue.default', 'queue.connections.%s.driver'];

    public function index(SondaDeBase $base): JsonResponse
    {
        $cacheEnRedis = $this->usa('redis', ...self::CACHE);
        $colaEnRedis = $this->usa('redis', ...self::COLA);
        $redis = $cacheEnRedis || $colaEnRedis || config('session.driver') === 'redis' ? $this->checkRedis() : null;
        $database = $base->responde();
        $cacheCaido = ($cacheEnRedis && $redis === false) || (! $database && $this->usa('database', ...self::CACHE));
        $colaCaida = ($colaEnRedis && $redis === false) || (! $database && $this->usa('database', ...self::COLA));

        $services = [
            'app' => true,
            'database' => $database,
            'cache' => $cacheCaido ? false : $this->checkCache(),
            'redis' => $redis,
            'cola_mail' => $colaCaida ? false : $this->checkColas(),
        ];

        $allHealthy = ! in_array(false, $services, true);

        $health = [
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ];

        return ApiResponse::success($health, null, $allHealthy ? 200 : 503);
    }

    private function checkCache(): bool
    {
        try {
            Cache::store()->put('health_check', true, 10);

            return Cache::store()->get('health_check') === true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** El job pendiente más viejo de cada cola no supera `salud.cola_max_segundos`. */
    private function checkColas(): bool
    {
        try {
            /** @var Queue $cola */
            $cola = app('queue')->connection();
            $limite = now()->getTimestamp() - (int) config('salud.cola_max_segundos');

            foreach ((array) config('salud.colas') as $nombre) {
                $creado = $cola->creationTimeOfOldestPendingJob((string) $nombre);

                if ($creado !== null && (int) $creado < $limite) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function usa(string $driver, string $claveDefault, string $claveDriver): bool
    {
        return config(sprintf($claveDriver, (string) config($claveDefault))) === $driver;
    }
}
