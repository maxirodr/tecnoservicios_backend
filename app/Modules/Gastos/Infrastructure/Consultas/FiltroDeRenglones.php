<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/**
 * Un filtro chico y componible sobre los renglones (RN-GAS-30, arquitectura §3): el mismo criterio en SQL
 * (qué gastos aparecen y el total de lo filtrado) y en PHP (la parte de cada fila, RN-GAS-31).
 */
interface FiltroDeRenglones
{
    /** Sobre una consulta de `gasto_renglones`. */
    public function aplicar(Builder $renglones): void;

    public function cumple(GastoRenglon $renglon): bool;

    /** false para el alcance (no cambia "la parte que cumple", solo lo que se ve). */
    public function esFiltroDeLaPersona(): bool;
}
