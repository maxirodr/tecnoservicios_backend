<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Base de los eventos de dominio de Pedidos (pedidos.md §8). Se despachan **después del commit** del caso
 * de uso; `actividadId` es la actividad registrada en la misma transacción y es la base del `dedupe_key`
 * de Avisos (avisos.md §6). Contrato compartido entre Pedidos (los dispara) y Avisos (los escucha).
 */
abstract readonly class EventoDePedido implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $pedidoId,
        public int $actorId,
        public int $actividadId,
        public Participantes $participantes,
    ) {}
}
