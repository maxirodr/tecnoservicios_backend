<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Obras\Domain\Models\Obra;

/** Una fila de W-05 (ObraFila): la obra + lo que viene de los puertos de Pedidos y Rendicion. */
final readonly class FilaObra
{
    public function __construct(
        public Obra $obra,
        public ?int $pedidosAbiertos,
        public ?string $porcentajeGastado,
        public ?bool $pasadoDePresupuesto,
    ) {}
}
