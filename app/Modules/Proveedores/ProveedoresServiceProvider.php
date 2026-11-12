<?php

declare(strict_types=1);

namespace App\Modules\Proveedores;

use App\Shared\Providers\ModuleServiceProvider;

final class ProveedoresServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
