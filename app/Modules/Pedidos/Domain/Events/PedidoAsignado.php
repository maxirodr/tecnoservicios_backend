<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al crear un pedido (avisos.md §6: `asignado`). */
final readonly class PedidoAsignado extends EventoDePedido {}
