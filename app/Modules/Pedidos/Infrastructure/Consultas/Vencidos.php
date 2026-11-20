<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/** "Solo vencidos": abiertos con la entrega antes de `$hoy`, en la hora de la empresa (RN-PED-36). */
final readonly class Vencidos implements FiltroPedidos
{
    public function __construct(private string $hoy) {}

    public function aplicar(Builder $query): void
    {
        PedidosQuery::vencidosAl($query, $this->hoy);
    }
}
