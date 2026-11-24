<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/** Filtro Obra = una obra (RN-GAS-30). */
final readonly class PorObra implements FiltroDeRenglones
{
    public function __construct(private int $obraId) {}

    public function aplicar(Builder $renglones): void
    {
        $renglones->where('gasto_renglones.obra_id', $this->obraId);
    }

    public function cumple(GastoRenglon $renglon): bool
    {
        return $renglon->obra_id === $this->obraId;
    }

    public function esFiltroDeLaPersona(): bool
    {
        return true;
    }
}
