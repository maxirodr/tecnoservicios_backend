<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traits;

use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Para los catch explícitos de un controller: registra el error en el canal `errors` con contexto
 * (lo lee /api/errors) y responde con el envelope sin filtrar detalles en producción.
 */
trait SafeErrorResponse
{
    protected function safeError(Throwable $e, ?string $fallback = null, int $status = 500): JsonResponse
    {
        $request = request();
        $route = $request->route();
        $fallback ??= ApiExceptionRenderer::messageFor($status);

        Log::channel('errors')->error($fallback, [
            'exception_class' => $e::class,
            'exception' => $e->getMessage(),
            'action' => $route instanceof Route ? $route->getActionName() : 'unknown',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $route instanceof Route ? $route->getName() : null,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'input' => $request->except(['password', 'password_confirmation', 'token', 'secret']),
            'file' => $e->getFile().':'.$e->getLine(),
            'trace_short' => self::shortTrace($e),
        ]);

        $errors = config('app.debug') ? ['debug' => ['message' => $e->getMessage()]] : null;

        return ApiResponse::error($fallback, $status, $errors);
    }

    /**
     * @return list<string>
     */
    public static function shortTrace(Throwable $e): array
    {
        return array_map(
            static fn (array $frame): string => ($frame['class'] ?? '')
                .($frame['type'] ?? '')
                .$frame['function']
                .' ('.basename($frame['file'] ?? '').':'.($frame['line'] ?? '?').')',
            array_slice($e->getTrace(), 0, 5),
        );
    }
}
