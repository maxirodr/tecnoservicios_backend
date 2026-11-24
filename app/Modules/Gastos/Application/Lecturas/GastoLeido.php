<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;

/**
 * Un gasto como lo ve quien mira (RN-GAS-48): con "solo sus obras", solo los renglones de sus obras y
 * los totales de esos; `totalFiltrado`, la parte que cumple los filtros de obra o rubro (RN-GAS-31).
 */
final readonly class GastoLeido
{
    /**
     * @param  list<GastoRenglon>  $renglones  los que ve
     * @param  numeric-string  $total
     * @param  numeric-string|null  $totalFiltrado
     * @param  array{editar: bool, anular: bool}  $puede
     */
    public function __construct(
        public Gasto $gasto,
        public array $renglones,
        public int $ocultos,
        public string $total,
        public ?string $totalFiltrado,
        public string $documento,
        public array $puede,
    ) {}
}
