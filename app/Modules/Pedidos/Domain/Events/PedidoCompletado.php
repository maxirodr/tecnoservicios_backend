<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al completarlo (`completado` y `completado_gente` al superior de quien lo pidió, DU-13). */
final readonly class PedidoCompletado extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        public string $comentario,
        public int $cantidadComprobantes = 0,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes);
    }
}
