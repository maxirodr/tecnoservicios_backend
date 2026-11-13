<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Filtros;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;

/** Filtro componible del listado, el combobox de la factura y el buscador general (arquitectura §3). */
interface FiltroProveedores
{
    /**
     * @param  Builder<Proveedor>  $query
     * @return Builder<Proveedor>
     */
    public function aplicar(Builder $query): Builder;
}
