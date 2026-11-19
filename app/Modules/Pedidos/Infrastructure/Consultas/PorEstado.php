<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** `abiertos` (por defecto), `todos` o un estado. */
final readonly class PorEstado implements FiltroPedidos
{
    public function __construct(private string $estado) {}

    public function aplicar(Builder $query): void
    {
        PedidosQuery::porEstado($query, $this->estado);
    }
}
