<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;

/** Hasta que Gastos implemente la lectura real: ninguna obra tiene gastos. */
final readonly class GastadoDeObrasNulo implements GastadoDeObras
{
    public function totalesPorRubro(array $obraIds): array
    {
        return [];
    }

    public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array
    {
        return [];
    }

    public function contarRenglonesPorMaterial(array $materialIds): array
    {
        return [];
    }
}
