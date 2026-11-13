<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use App\Shared\Actividad\EloquentRegistroActividad;
use App\Shared\Actividad\RegistroActividad;

/**
 * Lo transversal (app/Shared): registro de actividad (ADR-010).
 */
final class SharedServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegistroActividad::class, EloquentRegistroActividad::class);
    }

    protected function moduleDirectory(): string
    {
        return dirname(__DIR__);
    }
}
