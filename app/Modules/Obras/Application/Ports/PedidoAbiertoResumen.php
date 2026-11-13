<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Ports;

/** Un pedido abierto de la obra (no_iniciada, en_curso o bloqueada) para W-22 (obras.md §10.4). */
final readonly class PedidoAbiertoResumen
{
    public function __construct(
        public int $id,
        public string $tarea,
        public ?int $propietarioId,
        public ?string $propietarioNombre,
        public int $sectorId,
        public string $sectorNombre,
        public string $estado,
    ) {}
}
