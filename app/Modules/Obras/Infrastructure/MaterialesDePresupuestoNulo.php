<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Obras\Application\Ports\MaterialesDePresupuesto;

/** Sin control por cantidad: ningún presupuesto tiene materiales. Rendicion registra el suyo. */
final class MaterialesDePresupuestoNulo implements MaterialesDePresupuesto
{
    public function tieneMateriales(int $presupuestoId): bool
    {
        return false;
    }
}
