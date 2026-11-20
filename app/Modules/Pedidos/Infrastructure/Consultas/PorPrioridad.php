<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use Illuminate\Database\Eloquent\Builder;

final readonly class PorPrioridad implements FiltroPedidos
{
    public function __construct(private Prioridad $prioridad) {}

    public function aplicar(Builder $query): void
    {
        $query->where('pedidos.prioridad', $this->prioridad->value);
    }
}
