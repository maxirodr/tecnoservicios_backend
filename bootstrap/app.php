<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Support\ErrorLogContext;
use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\Middleware\RemoveStackHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RemoveStackHeaders::class);
        $middleware->statefulApi();
        // Proxies de confianza: config/trustedproxy.php (TRUSTED_PROXIES), nunca `*` (SEG-04).
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error logging (NexoSmart bug-report): todo error de servidor y toda validación fallida
        // va al canal `errors` con contexto; lo lee GET /api/errors.
        // Laravel ignora ValidationException por defecto: se reactiva solo para este canal (el callback corta con false).
        $exceptions->stopIgnoring(ValidationException::class);
        $exceptions->report(function (Throwable $e) {
            $request = request();

            if ($e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500)
            ) {
                return;
            }

            $route = $request->route();
            $baseContext = ErrorLogContext::fromRequest($request);

            if ($e instanceof ValidationException) {
                Log::channel('errors')->warning($e->getMessage(), $baseContext + [
                    'exception_class' => 'ValidationException',
                    'validation_errors' => ErrorLogContext::validationFields($e->errors()),
                ]);

                return false;
            }

            Log::channel('errors')->error($e->getMessage(), $baseContext + [
                'exception_class' => $e::class,
                'route_name' => $route instanceof Route ? $route->getName() : null,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace_short' => ErrorLogContext::shortTrace($e),
            ]);

            // Sigue el reporte por defecto (laravel.log).
            return false;
        });

        // API-only: toda excepción se responde con el envelope { data, message, errors }.
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => true);
        $exceptions->render(
            fn (Throwable $e, Request $request) => (new ApiExceptionRenderer((bool) config('app.debug')))->render($e, $request),
        );
    })->create();
