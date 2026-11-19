<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al tomarlo, también desde el sector (`tomado`). */
final readonly class PedidoTomado extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        public bool $desdeSector = false,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes);
    }
}
