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

    /**
     * N11 de rendicion.md para quien ve la rendición: con % (N3 > 0), pasada o no; sin % (N3 = 0 o sin
     * presupuesto), pasada solo si la rendición la marca (presupuesto cargado en 0 con gasto); si no, null.
     */
    public static function pasada(?string $porcentaje, bool $pasada): ?bool
    {
        return $porcentaje !== null || $pasada ? $pasada : null;
    }
}
