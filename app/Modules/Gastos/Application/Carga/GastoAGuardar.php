<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\TotalDeRenglon;

/**
 * Un gasto que pasó todas las reglas de la carga (gastos.md §4.3, §5): número normalizado, proveedor
 * resuelto (el genérico si no hay otro), fecha efectiva de cada renglón y su total calculado.
 */
final readonly class GastoAGuardar
{
    /**
     * @param  list<RenglonAGuardar>  $renglones
     */
    public function __construct(
        public Origen $origen,
        public ?string $numero,
        public string $fecha,
        public int $proveedorId,
        public ?string $rendidoPor,
        public ?string $periodoDesde,
        public ?string $periodoHasta,
        public array $renglones,
    ) {}

    /**
     * @return list<int>
     */
    public function obraIds(): array
    {
        $ids = [];

        foreach ($this->renglones as $renglon) {
            if ($renglon->obraId !== null) {
                $ids[$renglon->obraId] = $renglon->obraId;
            }
        }

        return array_values($ids);
    }

    /**
     * @return numeric-string
     */
    public function total(): string
    {
        return TotalDeRenglon::suma(array_map(static fn (RenglonAGuardar $r): string => $r->total, $this->renglones));
    }
}
