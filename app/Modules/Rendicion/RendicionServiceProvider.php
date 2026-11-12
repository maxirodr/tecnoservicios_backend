<?php

declare(strict_types=1);

namespace App\Modules\Rendicion;

use App\Shared\Providers\ModuleServiceProvider;

final class RendicionServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
