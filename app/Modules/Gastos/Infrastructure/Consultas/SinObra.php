<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/** Filtro Obra = "(sin obra)" (RN-GAS-30). */
final readonly class SinObra implements FiltroDeRenglones
{
    public function aplicar(Builder $renglones): void
    {
        $renglones->whereNull('gasto_renglones.obra_id');
    }

    public function cumple(GastoRenglon $renglon): bool
    {
        return $renglon->obra_id === null;
    }

    public function esFiltroDeLaPersona(): bool
    {
        return true;
    }
}
