<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Ports;

/** La obra de una fila de "Gastado por obra" (proveedores.md §10.3). */
final readonly class ObraGastada
{
    public function __construct(
        public int $id,
        public string $numero,
        public string $nombreTrabajo,
    ) {}
}
