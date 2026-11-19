<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Estados;

/** Las acciones que cambian (o pueden cambiar) el estado del pedido (pedidos.md §6.1). */
enum AccionPedido: string
{
    case Tomar = 'tomar';
    case Bloquear = 'bloquear';
    case Retomar = 'retomar';
    case Completar = 'completar';
    case Reabrir = 'reabrir';
    case Reasignar = 'reasignar';
}
