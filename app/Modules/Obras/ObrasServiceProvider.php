<?php

declare(strict_types=1);

namespace App\Modules\Obras;

use App\Shared\Providers\ModuleServiceProvider;

final class ObrasServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
