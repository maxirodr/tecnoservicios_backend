<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure\Consultas;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;

/** Punto de partida de las consultas de proveedores: suma filtros chicos (query object, arquitectura §3). */
final class ProveedoresQuery
{
    /**
     * @return Builder<Proveedor>
     */
    public static function con(FiltroProveedores ...$filtros): Builder
    {
        $query = Proveedor::query()->with('rubroHabitual');

        foreach ($filtros as $filtro) {
            $query = $filtro->aplicar($query);
        }

        return $query;
    }
}
