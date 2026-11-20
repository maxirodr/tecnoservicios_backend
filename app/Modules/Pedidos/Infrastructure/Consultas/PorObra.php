<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

final readonly class PorObra implements FiltroPedidos
{
    public function __construct(private int $obraId) {}

    public function aplicar(Builder $query): void
    {
        PedidosQuery::porObra($query, $this->obraId);
    }
}
