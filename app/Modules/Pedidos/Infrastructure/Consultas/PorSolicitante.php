<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

final readonly class PorSolicitante implements FiltroPedidos
{
    public function __construct(private int $solicitanteId) {}

    public function aplicar(Builder $query): void
    {
        $query->where('pedidos.solicitante_id', $this->solicitanteId);
    }
}
