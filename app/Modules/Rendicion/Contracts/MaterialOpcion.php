<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

/**
 * Un material de la obra para el selector de Gastos. `restaLlevar` es null en los fuera de presupuesto;
 * `precioPresupuestado` lo sugiere Gastos como precio del retiro (RN-GAS-24).
 */
final readonly class MaterialOpcion
{
    /**
     * @param  numeric-string|null  $restaLlevar  escala 3
     * @param  numeric-string|null  $precioPresupuestado  escala 2
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public string $unidad,
        public bool $fueraDePresupuesto,
        public ?string $restaLlevar,
        public ?string $precioPresupuestado,
    ) {}
}
