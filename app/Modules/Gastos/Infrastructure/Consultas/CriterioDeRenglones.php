<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use Illuminate\Database\Query\Builder;

/**
 * Los filtros de renglón juntos (obra, sin obra, rubro y el alcance): un gasto aparece si al menos uno de
 * sus renglones cumple todos, y su parte es la suma de esos (RN-GAS-31, resuelta DU-G06).
 */
final readonly class CriterioDeRenglones
{
    /**
     * @param  list<FiltroDeRenglones>  $filtros
     */
    public function __construct(private array $filtros = []) {}

    public function vacio(): bool
    {
        return $this->filtros === [];
    }

    /** Hay filtro de obra o de rubro elegido por la persona (no solo el alcance). */
    public function filtraRenglones(): bool
    {
        foreach ($this->filtros as $filtro) {
            if ($filtro->esFiltroDeLaPersona()) {
                return true;
            }
        }

        return false;
    }

    public function aplicar(Builder $renglones): void
    {
        foreach ($this->filtros as $filtro) {
            $filtro->aplicar($renglones);
        }
    }

    public function cumple(GastoRenglon $renglon): bool
    {
        foreach ($this->filtros as $filtro) {
            if (! $filtro->cumple($renglon)) {
                return false;
            }
        }

        return true;
    }
}
