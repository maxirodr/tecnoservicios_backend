<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Support\ErrorLogContext;
use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\Middleware\RemoveStackHeaders;
use App\Shared\Http\Middleware\SecurityHeaders;
use App\Shared\Providers\SharedServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RemoveStackHeaders::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();
        // API-only: sin sesión nunca se redirige (no existe la ruta `login`); el renderer responde 401 JSON.
        $middleware->redirectGuestsTo(null);
        // Límite general por usuario o IP (SEG-03): limitador `api` en SharedServiceProvider.
        $middleware->throttleApi(SharedServiceProvider::LIMITE_API);
        // Proxies de confianza: config/trustedproxy.php (TRUSTED_PROXIES), nunca `*` (SEG-04).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error logging (NexoSmart bug-report): solo errores del sistema (5xx, excepciones inesperadas)
        // van al canal `errors` con contexto; lo lee GET /api/errors. Las respuestas esperadas
        // (422, 401, 403, 404, 409, 429) no se registran: Laravel ya ignora ValidationException.
        $exceptions->report(function (Throwable $e) {
            $request = request();

            if ($e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500)
            ) {
                return;
            }

            $route = $request->route();
            Log::channel('errors')->error($e->getMessage(), ErrorLogContext::fromRequest($request) + [
                'exception_class' => $e::class,
                'route_name' => $route instanceof Route ? $route->getName() : null,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace_short' => ErrorLogContext::shortTrace($e),
            ]);

            // Ya quedó en el canal `errors`: false corta el reporte por defecto (no se duplica en laravel.log).
            return false;
        });

        // API-only: toda excepción se responde con el envelope { data, message, errors }.
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => true);
        $exceptions->render(
            fn (Throwable $e, Request $request) => (new ApiExceptionRenderer((bool) config('app.debug')))->render($e, $request),
        );
    })->create();
