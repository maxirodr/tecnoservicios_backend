<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** RN-PRV-02 (resuelta DU-A20): orden alfabético por nombre, sin distinguir mayúsculas ni tildes. */
final readonly class Alfabetico implements FiltroProveedores
{
    public function aplicar(Builder $query): Builder
    {
        return $query->orderBy('nombre_busqueda')->orderBy('id');
    }
}
