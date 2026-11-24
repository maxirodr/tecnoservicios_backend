<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use Illuminate\Database\Query\Builder;

/** Filtro Proveedor (el genérico también, RN-GAS-30). */
final readonly class PorProveedor implements FiltroGastos
{
    public function __construct(private int $proveedorId) {}

    public function aplicar(Builder $gastos): void
    {
        $gastos->where('gastos.proveedor_id', $this->proveedorId);
    }
}
