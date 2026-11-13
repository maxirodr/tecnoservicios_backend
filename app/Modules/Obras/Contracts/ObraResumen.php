<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/** Lo mínimo de una obra para otros módulos (obras.md §10.4): pedidos, gastos, buscador. */
final readonly class ObraResumen
{
    public function __construct(
        public int $id,
        public string $numero,
        public string $nombreTrabajo,
        public string $cliente,
        public string $estado,
        public int $responsableId,
    ) {}
}
