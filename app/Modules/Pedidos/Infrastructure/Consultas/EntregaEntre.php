<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** "Entrega: desde → hasta", inclusive; cualquiera de los dos puede faltar ("AAAA-MM-DD"). */
final readonly class EntregaEntre implements FiltroPedidos
{
    public function __construct(
        private ?string $desde,
        private ?string $hasta,
    ) {}

    public function aplicar(Builder $query): void
    {
        if ($this->desde !== null) {
            $query->where('pedidos.fecha_entrega', '>=', $this->desde);
        }

        if ($this->hasta !== null) {
            $query->where('pedidos.fecha_entrega', '<=', $this->hasta);
        }
    }
}
