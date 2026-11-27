<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

/**
 * Qué ve de un gasto quien mira, además del alcance: el detalle por persona del parte de horas (RN-GAS-57,
 * `gastos = editar`), el estado de pago (RN-GAS-61, `pagos` ≥ ver) y si puede marcarlo (RN-GAS-59).
 */
final readonly class VistaDelGasto
{
    public function __construct(
        public bool $detallePorPersona = true,
        public bool $vePago = false,
        public bool $marcaPago = false,
    ) {}
}
