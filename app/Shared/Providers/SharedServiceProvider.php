<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use App\Shared\Actividad\EloquentRegistroActividad;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Idempotencia\Idempotent;
use App\Shared\Idempotencia\PurgarIdempotencyKeysCommand;
use Illuminate\Routing\Router;

/**
 * Lo transversal (app/Shared): registro de actividad (ADR-010) y middleware `idempotent` (ADR-011).
 */
final class SharedServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegistroActividad::class, EloquentRegistroActividad::class);
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('idempotent', Idempotent::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PurgarIdempotencyKeysCommand::class]);
        }

        parent::boot();
    }

    protected function moduleDirectory(): string
    {
        return dirname(__DIR__);
    }
}
