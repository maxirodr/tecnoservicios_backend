<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al reabrirlo (`reabierto`) al propietario, o al que se eligió si estaba inactivo (resuelta DU-P09). */
final readonly class PedidoReabierto extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        public string $comentario,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes);
    }
}
