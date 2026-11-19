<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Comentario nuevo (`comentado`); quién recibe depende de quién comenta (resuelta DU-P23). */
final readonly class PedidoComentado extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        FotoDelPedido $foto,
        public int $comentarioId,
        public string $texto,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes, $foto);
    }
}
