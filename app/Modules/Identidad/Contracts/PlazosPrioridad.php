<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeInterface;

/**
 * RN-IDE-44: prioridad de un pedido con los plazos vigentes de W-50 (días corridos, RN-IDE-41).
 * La usa Pedidos (semana 2) al cargar el pedido o al cambiar su entrega.
 */
interface PlazosPrioridad
{
    public function calcular(DateTimeInterface $fechaPedido, DateTimeInterface $fechaEntrega): Prioridad;
}
