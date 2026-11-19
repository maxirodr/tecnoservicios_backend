<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Prioridad;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeInterface;

/**
 * RN-PED-14 (arquitectura §3 "Strategy"): cómo queda la prioridad de un pedido. `PorAnticipacion` la
 * calcula con los plazos vigentes; `Manual` conserva la que eligió quien lo pidió.
 */
interface EstrategiaPrioridad
{
    public function prioridad(?DateTimeInterface $fechaPedido, ?DateTimeInterface $fechaEntrega, Prioridad $actual): Prioridad;
}
