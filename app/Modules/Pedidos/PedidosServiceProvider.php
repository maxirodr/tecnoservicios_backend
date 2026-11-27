<?php

declare(strict_types=1);

namespace App\Modules\Pedidos;

use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Pedidos\Application\Acceso\ComprobanteAdjuntos;
use App\Modules\Pedidos\Application\Acceso\EquipoAdjuntos;
use App\Modules\Pedidos\Application\Acceso\MiGente;
use App\Modules\Pedidos\Application\Acceso\PedidoAdjuntos;
use App\Modules\Pedidos\Application\CargaInicial\CargaInicialDeTareasConCasosDeUso;
use App\Modules\Pedidos\Contracts\CargaInicialDeTareas;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
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
 * (reemplaza a `PedidosDeObraNulo`, HU-059 c4), el de Identidad `PedidosDePersonasYSectores` (HU-058), el
 * grupo Pedidos del buscador (HU-057), la carga inicial de tareas (`CargaInicialDeTareas`, importacion.md §11.4)
 * y los contextos de adjuntos `pedido`, `comprobante_cierre`, `equipo_inventario` y `equipo_presupuesto`.
 */
final class PedidosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PedidosParaRecordar::class, PedidosParaRecordarEloquent::class);
        // `bind` (no `bindIf`): pisa las nulas de Obras e Identidad sin depender del orden de los providers.
        $this->app->bind(PedidosDeObra::class, PedidosDeObraEloquent::class);
        $this->app->bind(PedidosDePersonasYSectores::class, PedidosDePersonasYSectoresEloquent::class);
        $this->app->bind(CargaInicialDeTareas::class, CargaInicialDeTareasConCasosDeUso::class);
        // "Mi gente" se recuerda solo mientras dura el pedido al servidor.
        $this->app->scoped(MiGente::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Alias estables para `actividades.sujeto_type` y `adjuntos.sujeto_type` (antes de la primera escritura).
        Relation::morphMap([Pedido::MORPH => Pedido::class, EquipoReparacion::MORPH => EquipoReparacion::class]);

        $contextos = $this->app->make(ContextosAdjuntos::class);
        $contextos->registrar(Pedido::CONTEXTO_ADJUNTOS, PedidoAdjuntos::class);
        $contextos->registrar(Pedido::CONTEXTO_COMPROBANTES, ComprobanteAdjuntos::class);
        $contextos->registrar(EquipoReparacion::CONTEXTO_INVENTARIO, EquipoAdjuntos::class);
        $contextos->registrar(EquipoReparacion::CONTEXTO_PRESUPUESTO, EquipoAdjuntos::class);

        $this->app->make(FuentesBusqueda::class)->registrar(PedidosEnBuscador::TIPO, PedidosEnBuscador::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
