<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/**
 * Quiénes son parte del pedido **después** de la acción (pedidos.md §8). Con el pedido en el sector,
 * `propietarioId` es null y Avisos lo manda a las personas activas de `sectorId`.
 */
final readonly class Participantes
{
    /**
     * @param  list<int>  $seguidorIds
     */
    public function __construct(
        public ?int $solicitanteId,
        public ?int $propietarioId,
        public int $sectorId,
        public array $seguidorIds = [],
    ) {}
}
