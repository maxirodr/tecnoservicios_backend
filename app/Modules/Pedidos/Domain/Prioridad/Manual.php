<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Prioridad;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeInterface;

/** La prioridad corregida a mano se mantiene aunque cambien las fechas (RN-PED-13, resuelta DU-A13). */
final class Manual implements EstrategiaPrioridad
{
    public function prioridad(?DateTimeInterface $fechaPedido, ?DateTimeInterface $fechaEntrega, Prioridad $actual): Prioridad
    {
        return $actual;
    }
}
