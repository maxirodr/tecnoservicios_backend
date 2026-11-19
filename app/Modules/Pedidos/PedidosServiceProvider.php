<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use App\Modules\Pedidos\Infrastructure\PedidosParaRecordarNulo;
use App\Shared\Providers\ModuleServiceProvider;

final class PedidosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // La implementación real la registra este módulo al construir Pedidos (semana 2).
        $this->app->bindIf(PedidosParaRecordar::class, PedidosParaRecordarNulo::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
