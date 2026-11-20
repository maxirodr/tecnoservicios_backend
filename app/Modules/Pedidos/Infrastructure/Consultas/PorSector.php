<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

final readonly class PorSector implements FiltroPedidos
{
    public function __construct(private int $sectorId) {}

    public function aplicar(Builder $query): void
    {
        $query->where('pedidos.sector_id', $this->sectorId);
    }
}
