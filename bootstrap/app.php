<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Traits\SafeErrorResponse;
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
        $middleware->trustProxies(at: '*');
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
            $baseContext = [
                'action' => $route instanceof Route ? $route->getActionName() : 'unknown',
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
                'input' => $request->except(['password', 'password_confirmation', 'actual', 'nueva', 'nueva_confirmation', 'token', 'secret']),
            ];

            if ($e instanceof ValidationException) {
                Log::channel('errors')->warning($e->getMessage(), $baseContext + [
                    'exception_class' => 'ValidationException',
                    'validation_errors' => $e->errors(),
                ]);

                return false;
            }

            Log::channel('errors')->error($e->getMessage(), $baseContext + [
                'exception_class' => $e::class,
                'route_name' => $route instanceof Route ? $route->getName() : null,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace_short' => SafeErrorResponse::shortTrace($e),
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
