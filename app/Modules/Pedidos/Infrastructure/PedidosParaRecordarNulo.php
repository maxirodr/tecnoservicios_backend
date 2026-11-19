<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure;

use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use DateTimeInterface;

/** Hasta que Pedidos registre la suya (semana 2, tanda 1): no hay pedidos que recordar. */
final class PedidosParaRecordarNulo implements PedidosParaRecordar
{
    public function conEntregaEl(DateTimeInterface $fecha): array
    {
        return [];
    }
}
