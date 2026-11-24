<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Infrastructure;

use App\Modules\Obras\Application\Ports\MaterialesDePresupuesto;
use App\Modules\Rendicion\Domain\Models\Material;

/** Un adicional con materiales del control por cantidad no se quita (RN-OBR-24, RN-REN-53). */
final readonly class MaterialesDePresupuestoEloquent implements MaterialesDePresupuesto
{
    public function tieneMateriales(int $presupuestoId): bool
    {
        return Material::query()->where('presupuesto_id', $presupuestoId)->exists();
    }
}
