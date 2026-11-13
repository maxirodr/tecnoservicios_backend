<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traits;

use App\Http\Controllers\Api\Support\ErrorLogContext;
use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Para los catch explícitos de un controller: registra el error en el canal `errors` con contexto
 * sin valores del input (ErrorLogContext; lo lee /api/errors) y responde con el envelope sin filtrar detalles en producción.
 */
trait SafeErrorResponse
{
    protected function safeError(Throwable $e, ?string $fallback = null, int $status = 500): JsonResponse
    {
        $request = request();
        $route = $request->route();
        $fallback ??= ApiExceptionRenderer::messageFor($status);

        Log::channel('errors')->error($fallback, ErrorLogContext::fromRequest($request) + [
            'exception_class' => $e::class,
            'exception' => $e->getMessage(),
            'route_name' => $route instanceof Route ? $route->getName() : null,
            'file' => $e->getFile().':'.$e->getLine(),
            'trace_short' => ErrorLogContext::shortTrace($e),
        ]);

        $errors = config('app.debug') ? ['debug' => ['message' => $e->getMessage()]] : null;

        return ApiResponse::error($fallback, $status, $errors);
    }
}
