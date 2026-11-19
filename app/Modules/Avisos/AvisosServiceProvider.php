<?php

declare(strict_types=1);

namespace App\Modules\Avisos;

use App\Modules\Avisos\Application\DestinatariosDeAsignacion;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoAsignado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoBloqueado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoComentado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoCompletado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoReabierto;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoReasignado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoTomado;
use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Avisos\Infrastructure\AvisosDelPedidoEnLaBase;
use App\Modules\Avisos\Infrastructure\Console\RecordatoriosCommand;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Events\PedidoBloqueado;
use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use App\Modules\Pedidos\Domain\Events\PedidoReabierto;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;

final class AvisosServiceProvider extends ModuleServiceProvider
{
    /** Un listener por evento de Pedidos (RN-AVI-01); los eventos se despachan después del commit. */
    private const LISTENERS = [
        PedidoAsignado::class => AvisarPedidoAsignado::class,
        PedidoReasignado::class => AvisarPedidoReasignado::class,
        PedidoTomado::class => AvisarPedidoTomado::class,
        PedidoBloqueado::class => AvisarPedidoBloqueado::class,
        PedidoComentado::class => AvisarPedidoComentado::class,
        PedidoCompletado::class => AvisarPedidoCompletado::class,
        PedidoReabierto::class => AvisarPedidoReabierto::class,
    ];

    public function register(): void
    {
        $this->app->bind(AvisosDelPedido::class, AvisosDelPedidoEnLaBase::class);
        $this->app->bind(DestinatariosDeAviso::class, DestinatariosDeAsignacion::class);
    }

    public function boot(): void
    {
        foreach (self::LISTENERS as $evento => $listener) {
            Event::listen($evento, $listener);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([RecordatoriosCommand::class]);
        }

        parent::boot();
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
