<?php

declare(strict_types=1);

namespace App\Modules\Gastos;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Infrastructure\GastadoDeObrasNulo;
use App\Shared\Providers\ModuleServiceProvider;

final class GastosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // Gastos cambia la nula por la lectura real (semana 3, rendicion.md §12.4.1).
        $this->app->bindIf(GastadoDeObras::class, GastadoDeObrasNulo::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
