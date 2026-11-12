<?php

declare(strict_types=1);

namespace App\Modules\Importacion;

use App\Shared\Providers\ModuleServiceProvider;

final class ImportacionServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
