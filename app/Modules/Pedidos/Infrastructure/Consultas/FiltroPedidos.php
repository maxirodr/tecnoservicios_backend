<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Database\Eloquent\Builder;

/** Un filtro chico y componible de Todos los pedidos (RN-PED-43, query object de arquitectura §3). */
interface FiltroPedidos
{
    /**
     * @param  Builder<Pedido>  $query
     */
    public function aplicar(Builder $query): void;
}
