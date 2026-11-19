<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** "Sin obra / Servicios generales" (F-36). */
final readonly class SinObra implements FiltroPedidos
{
    public function aplicar(Builder $query): void
    {
        PedidosQuery::porObra($query, PedidosQuery::SIN_OBRA);
    }
}
