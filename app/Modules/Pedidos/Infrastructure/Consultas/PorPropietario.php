<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

final readonly class PorPropietario implements FiltroPedidos
{
    public function __construct(private int $propietarioId) {}

    public function aplicar(Builder $query): void
    {
        $query->where('pedidos.propietario_id', $this->propietarioId);
    }
}
