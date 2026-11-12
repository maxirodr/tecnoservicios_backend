<?php

declare(strict_types=1);

namespace App\Modules\Gastos;

use App\Shared\Providers\ModuleServiceProvider;

final class GastosServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
