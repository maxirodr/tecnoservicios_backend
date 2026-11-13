<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Infrastructure\Consultas\Alfabetico;
use App\Modules\Proveedores\Infrastructure\Consultas\PorNombre;
use App\Modules\Proveedores\Infrastructure\Consultas\ProveedoresQuery;
use Illuminate\Database\Eloquent\Collection;

/** F-42: lo necesario para elegir el proveedor en el combobox de la factura (máx. 20, proveedores.md §10.1). */
final class OpcionesProveedoresQuery
{
    private const MAXIMO = 20;

    /**
     * @return Collection<int, Proveedor>
     */
    public function handle(?string $buscar): Collection
    {
        return ProveedoresQuery::con(new PorNombre($buscar), new Alfabetico)->limit(self::MAXIMO)->get();
    }
}
