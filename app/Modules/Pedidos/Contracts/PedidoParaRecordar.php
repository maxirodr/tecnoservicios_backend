<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Contracts;

/** Un pedido abierto con entrega en la fecha pedida, para el "vence mañana" de Avisos (avisos.md RN-AVI-16). */
final readonly class PedidoParaRecordar
{
    public function __construct(
        public int $pedidoId,
        public ?int $propietarioId,
        public int $sectorId,
        public string $tarea,
        public ?string $obraNombre,
        /** "2026-11-20" (fecha de calendario, hora de Argentina). */
        public string $fechaEntrega,
        /** Para el link a la obra del aviso; null = pedido sin obra. */
        public ?int $obraId = null,
    ) {}
}
