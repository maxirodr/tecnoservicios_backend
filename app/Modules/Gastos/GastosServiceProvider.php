<?php

declare(strict_types=1);

namespace App\Modules\Gastos;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\GastoAdjuntos;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Infrastructure\GastadoDeObrasEloquent;
use App\Modules\Gastos\Infrastructure\GastadoPorProveedorEloquent;
use App\Modules\Gastos\Infrastructure\GastosEnBuscador;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Shared\Adjuntos\ContextosAdjuntos;
use App\Shared\Busqueda\FuentesBusqueda;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Gastos (gastos.md): rutas, `GastadoDeObras` para Rendicion (reemplaza a `GastadoDeObrasNulo`,
 * rendicion.md §12.4.1), el puerto `GastadoPorProveedor` de Proveedores (reemplaza al nulo, HU-084), el
 * grupo Gastos del buscador (HU-085) y los contextos de adjuntos `comprobante_gasto` y `remito`
 * (identidad.md §6.6).
 */
final class GastosServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // `bind` (no `bindIf`): pisa las nulas sin depender del orden de los providers.
        $this->app->bind(GastadoDeObras::class, GastadoDeObrasEloquent::class);
        $this->app->bind(GastadoPorProveedor::class, GastadoPorProveedorEloquent::class);
        // El alcance y el catálogo se recuerdan solo mientras dura el pedido al servidor.
        $this->app->scoped(AccesoGasto::class);
        $this->app->scoped(Rubros::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Alias estable para `actividades.sujeto_type` y `adjuntos.sujeto_type`.
        Relation::morphMap([Gasto::MORPH => Gasto::class]);

        $contextos = $this->app->make(ContextosAdjuntos::class);
        $contextos->registrar(Gasto::CONTEXTO_COMPROBANTE, GastoAdjuntos::class);
        $contextos->registrar(Gasto::CONTEXTO_REMITO, GastoAdjuntos::class);

        $this->app->make(FuentesBusqueda::class)->registrar(GastosEnBuscador::TIPO, GastosEnBuscador::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
