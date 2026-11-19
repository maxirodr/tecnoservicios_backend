<?php

declare(strict_types=1);

namespace App\Modules\Avisos;

use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Avisos\Infrastructure\AvisosDelPedidoNulo;
use App\Modules\Avisos\Infrastructure\DestinatariosDeAvisoNulo;
use App\Shared\Providers\ModuleServiceProvider;

final class AvisosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // La implementación real la registra este módulo al construir Avisos (semana 2).
        $this->app->bindIf(AvisosDelPedido::class, AvisosDelPedidoNulo::class);
        $this->app->bindIf(DestinatariosDeAviso::class, DestinatariosDeAvisoNulo::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
