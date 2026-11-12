<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base de los providers de módulo: carga `routes.php` del módulo bajo el prefijo /api
 * con el middleware 'api', así sumar un módulo no toca routes/api.php.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function moduleDirectory(): string;

    public function boot(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $routesFile = $this->moduleDirectory().DIRECTORY_SEPARATOR.'routes.php';

        if (is_file($routesFile)) {
            Route::prefix('api')->middleware('api')->group($routesFile);
        }
    }
}
