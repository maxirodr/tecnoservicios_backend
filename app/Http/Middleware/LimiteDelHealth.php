<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límite por IP de GET /api/health sobre un cache que no es Redis (PU-020): el limitador general de la
 * API vive en Redis y, con Redis caído, el health nunca llegaba a contestar.
 */
final readonly class LimiteDelHealth
{
    private const DECAY_SEGUNDOS = 60;

    public function __construct(private CacheManager $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $limitador = new RateLimiter($this->cache->store((string) config('salud.limite_cache_store')));
        $clave = 'health|'.$request->ip();

        if ($limitador->tooManyAttempts($clave, (int) config('salud.pedidos_por_minuto'))) {
            return ApiResponse::error(ApiExceptionRenderer::messageFor(429), 429, null, [
                'Retry-After' => (string) $limitador->availableIn($clave),
            ]);
        }

        $limitador->hit($clave, self::DECAY_SEGUNDOS);

        return $next($request);
    }
}
