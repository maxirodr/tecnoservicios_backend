<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Shared\Providers\ModuleServiceProvider;

final class PedidosServiceProvider extends ModuleServiceProvider
{
    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
