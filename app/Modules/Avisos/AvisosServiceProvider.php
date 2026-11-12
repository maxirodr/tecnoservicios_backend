<?php

declare(strict_types=1);

namespace App\Modules\Avisos;

use App\Shared\Providers\ModuleServiceProvider;

final class AvisosServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
