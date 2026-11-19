<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Pedidos\Application\Acceso\ComprobanteAdjuntos;
use App\Modules\Pedidos\Application\Acceso\MiGente;
use App\Modules\Pedidos\Application\Acceso\PedidoAdjuntos;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\PedidosDeObraEloquent;
use App\Modules\Pedidos\Infrastructure\PedidosDePersonasYSectoresEloquent;
use App\Modules\Pedidos\Infrastructure\PedidosEnBuscador;
use App\Modules\Pedidos\Infrastructure\PedidosParaRecordarEloquent;
use App\Shared\Adjuntos\ContextosAdjuntos;
use App\Shared\Busqueda\FuentesBusqueda;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Pedidos (pedidos.md): rutas, `PedidosParaRecordar` para Avisos, el puerto `PedidosDeObra` de Obras
 * (reemplaza a `PedidosDeObraNulo`, HU-059 c4) y los contextos de adjuntos `pedido` y `comprobante_cierre`.
 */
final class PedidosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PedidosParaRecordar::class, PedidosParaRecordarEloquent::class);
        // `bind` (no `bindIf`): pisa la nula de Obras sin depender del orden de los providers.
        $this->app->bind(PedidosDeObra::class, PedidosDeObraEloquent::class);
        $this->app->bind(PedidosDePersonasYSectores::class, PedidosDePersonasYSectoresEloquent::class);
        // "Mi gente" se recuerda solo mientras dura el pedido al servidor.
        $this->app->scoped(MiGente::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Alias estable para `actividades.sujeto_type` y `adjuntos.sujeto_type` (antes de la primera escritura).
        Relation::morphMap([Pedido::MORPH => Pedido::class]);

        $contextos = $this->app->make(ContextosAdjuntos::class);
        $contextos->registrar(Pedido::CONTEXTO_ADJUNTOS, PedidoAdjuntos::class);
        $contextos->registrar(Pedido::CONTEXTO_COMPROBANTES, ComprobanteAdjuntos::class);

        $this->app->make(FuentesBusqueda::class)->registrar(PedidosEnBuscador::TIPO, PedidosEnBuscador::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
