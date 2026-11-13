<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Database\Eloquent\Builder;

/** Un filtro chico y componible del listado de obras (query object, arquitectura §3). */
interface FiltroObras
{
    /**
     * @param  Builder<Obra>  $query
     */
    public function aplicar(Builder $query): void;
}
