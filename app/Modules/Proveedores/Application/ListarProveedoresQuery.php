<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Proveedores\Application\Filtros\Alfabetico;
use App\Modules\Proveedores\Application\Filtros\PorNombre;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** F-55 · W-14 (RN-PRV-01…03): la base, alfabética, con buscador por nombre y paginada. */
final class ListarProveedoresQuery
{
    private const POR_PAGINA = 25;

    /**
     * @return LengthAwarePaginator<int, Proveedor>
     */
    public function handle(?string $buscar): LengthAwarePaginator
    {
        return ProveedoresQuery::con(new PorNombre($buscar), new Alfabetico)->paginate(self::POR_PAGINA);
    }
}
