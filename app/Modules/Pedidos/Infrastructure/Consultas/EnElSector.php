<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** Propietario "En el sector, sin persona" (resuelta DU-16). */
final readonly class EnElSector implements FiltroPedidos
{
    public function aplicar(Builder $query): void
    {
        $query->whereNull('pedidos.propietario_id');
    }
}
