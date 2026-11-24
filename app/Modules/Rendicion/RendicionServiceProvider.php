<?php

declare(strict_types=1);

namespace App\Modules\Rendicion;

use App\Modules\Gastos\Domain\Events\GastoAnulado;
use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Obras\Application\Ports\MaterialesDePresupuesto;
use App\Modules\Obras\Domain\Events\PresupuestoMontosCambiados;
use App\Modules\Rendicion\Application\Rendicion\InvalidarRendicion;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Infrastructure\AvanceGastoDeObraEloquent;
use App\Modules\Rendicion\Infrastructure\CatalogoDeRubrosEloquent;
use App\Modules\Rendicion\Infrastructure\MaterialesDeObraEloquent;
use App\Modules\Rendicion\Infrastructure\MaterialesDePresupuestoEloquent;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Rendicion (rendicion.md): rutas, `CatalogoDeRubros` y `MaterialesDeObra` para Gastos (reemplaza a la nula),
 * los puertos de Obras `AvanceGastoDeObra` (reemplaza a `AvanceGastoDeObraNulo`) y `MaterialesDePresupuesto`,
 * y la invalidación del cache de la rendición con los eventos de Gastos y de Obras (RN-REN-60).
 */
final class RendicionServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CatalogoDeRubros::class => CatalogoDeRubrosEloquent::class,
    ];

    public function register(): void
    {
        // `bind` (no `bindIf`): pisa las nulas de Rendicion y Obras sin depender del orden de los providers.
        $this->app->bind(MaterialesDeObra::class, MaterialesDeObraEloquent::class);
        $this->app->bind(AvanceGastoDeObra::class, AvanceGastoDeObraEloquent::class);
        $this->app->bind(MaterialesDePresupuesto::class, MaterialesDePresupuestoEloquent::class);
    }

    public function boot(): void
    {
        parent::boot();

        Relation::morphMap([Material::MORPH => Material::class]);

        $eventos = $this->app->make(Dispatcher::class);
        foreach ([GastoImputado::class, GastoCorregido::class, GastoAnulado::class] as $evento) {
            $eventos->listen($evento, [InvalidarRendicion::class, 'deGasto']);
        }
        $eventos->listen(PresupuestoMontosCambiados::class, [InvalidarRendicion::class, 'dePresupuesto']);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
