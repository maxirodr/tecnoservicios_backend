<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Prioridad;

use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeInterface;

/**
 * RN-PED-11: días corridos entre el pedido y la entrega con los plazos **vigentes** de W-50. Sin alguna de
 * las dos fechas (carga inicial) conserva la importada y no se recalcula (RN-PED-14, resuelta DU-20).
 */
final readonly class PorAnticipacion implements EstrategiaPrioridad
{
    public function __construct(private PlazosPrioridad $plazos) {}

    public function prioridad(?DateTimeInterface $fechaPedido, ?DateTimeInterface $fechaEntrega, Prioridad $actual): Prioridad
    {
        if ($fechaPedido === null || $fechaEntrega === null) {
            return $actual;
        }

        return $this->plazos->calcular($fechaPedido, $fechaEntrega);
    }
}
