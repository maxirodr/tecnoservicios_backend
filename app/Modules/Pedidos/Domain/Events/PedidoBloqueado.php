<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al bloquearlo, siempre con motivo (`bloqueado`). */
final readonly class PedidoBloqueado extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        public string $motivo,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes);
    }
}
