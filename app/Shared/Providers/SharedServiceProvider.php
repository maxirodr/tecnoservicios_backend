<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use App\Shared\Actividad\EloquentRegistroActividad;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\Console\PurgarAdjuntosHuerfanosCommand;
use App\Shared\Adjuntos\ContextosAdjuntos;
use App\Shared\Idempotencia\Idempotent;
use App\Shared\Idempotencia\PurgarIdempotencyKeysCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Lo transversal (app/Shared): registro de actividad (ADR-010), middleware `idempotent` (ADR-011)
 * y adjuntos (ADR-014) con su registro de contextos y sus rutas.
 */
final class SharedServiceProvider extends ModuleServiceProvider
{
    public const LIMITE_SUBIDAS = 'adjuntos';

    public function register(): void
    {
        $this->app->bind(RegistroActividad::class, EloquentRegistroActividad::class);
        $this->app->singleton(ContextosAdjuntos::class);
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('idempotent', Idempotent::class);

        RateLimiter::for(self::LIMITE_SUBIDAS, static fn (Request $request): Limit => Limit::perMinute(
            (int) config('adjuntos.subidas_por_minuto'),
        )->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        if ($this->app->runningInConsole()) {
            $this->commands([PurgarAdjuntosHuerfanosCommand::class, PurgarIdempotencyKeysCommand::class]);
        }

        parent::boot();
    }

    protected function moduleDirectory(): string
    {
        return dirname(__DIR__);
    }
}
