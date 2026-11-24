<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Monto de la obra (C19, DU-19, RN-OBR-36): lo que se le cobra al cliente, sin IVA, suma de los montos de
 * sus presupuestos. `porPresupuesto` trae todos, con `monto` null si ese no lo tiene cargado (RN-REN-24).
 */
final readonly class MontoDeObra
{
    /**
     * @param  numeric-string  $total
     * @param  list<MontoDePresupuesto>  $porPresupuesto
     */
    public function __construct(
        public string $total,
        public array $porPresupuesto,
    ) {}
}
