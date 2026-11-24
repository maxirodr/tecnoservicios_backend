<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use Illuminate\Database\Query\Builder;

/** Un filtro chico y componible sobre la cabecera (`gastos`), RN-GAS-30. */
interface FiltroGastos
{
    public function aplicar(Builder $gastos): void;
}
