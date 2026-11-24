<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/** Filtro Rubro = uno de los 13, como se imputó (EPP aparte, RN-GAS-30). */
final readonly class PorRubro implements FiltroDeRenglones
{
    public function __construct(private int $rubroId) {}

    public function aplicar(Builder $renglones): void
    {
        $renglones->where('gasto_renglones.rubro_id', $this->rubroId);
    }

    public function cumple(GastoRenglon $renglon): bool
    {
        return $renglon->rubro_id === $this->rubroId;
    }

    public function esFiltroDeLaPersona(): bool
    {
        return true;
    }
}
