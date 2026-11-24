<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/**
 * El alcance de quien mira (RN-GAS-48), que se aplica siempre antes: con "solo sus obras", solo los
 * renglones de las obras donde es responsable (nunca los "sin obra").
 */
final readonly class VisiblesPara implements FiltroDeRenglones
{
    /**
     * @param  list<int>  $obraIds
     */
    public function __construct(private array $obraIds) {}

    public function aplicar(Builder $renglones): void
    {
        $renglones->whereIn('gasto_renglones.obra_id', $this->obraIds === [] ? [0] : $this->obraIds);
    }

    public function cumple(GastoRenglon $renglon): bool
    {
        return $renglon->obra_id !== null && in_array($renglon->obra_id, $this->obraIds, true);
    }

    public function esFiltroDeLaPersona(): bool
    {
        return false;
    }
}
