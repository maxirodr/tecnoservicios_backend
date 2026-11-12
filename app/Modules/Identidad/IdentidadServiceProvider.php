<?php

declare(strict_types=1);

namespace App\Modules\Identidad;

use App\Shared\Providers\ModuleServiceProvider;

final class IdentidadServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
