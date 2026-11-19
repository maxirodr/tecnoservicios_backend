<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/** Al reasignar a una persona o al sector (`reasignado`); el comentario opcional de W-31 viaja en el aviso. */
final readonly class PedidoReasignado extends EventoDePedido
{
    public function __construct(
        int $pedidoId,
        int $actorId,
        int $actividadId,
        Participantes $participantes,
        public ?int $propietarioAnteriorId,
        public bool $aSector,
        public ?string $comentario = null,
    ) {
        parent::__construct($pedidoId, $actorId, $actividadId, $participantes);
    }
}
