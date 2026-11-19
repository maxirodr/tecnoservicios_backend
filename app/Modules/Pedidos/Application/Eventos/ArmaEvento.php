<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Eventos;

use App\Modules\Pedidos\Application\Acceso\Seguimiento;
use App\Modules\Pedidos\Domain\Events\FotoDelPedido;
use App\Modules\Pedidos\Domain\Events\Participantes;
use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * Lo que viaja en cada evento de Pedidos (pedidos.md §8): quiénes son parte del pedido y la foto del
 * pedido **después** de la acción. Se arma dentro de la transacción; el evento sale después del commit.
 */
final readonly class ArmaEvento
{
    public function __construct(private Seguimiento $seguimiento) {}

    public function participantes(Pedido $pedido): Participantes
    {
        return new Participantes(
            $pedido->solicitante_id,
            $pedido->propietario_id,
            $pedido->sector_id,
            $this->seguimiento->seguidorIds($pedido->id),
        );
    }

    public function foto(Pedido $pedido): FotoDelPedido
    {
        $obra = $pedido->obra_id === null ? null : $pedido->loadMissing('obra')->obra;

        return new FotoDelPedido(
            $pedido->tarea,
            $pedido->obra_id,
            $obra?->nombre_trabajo,
            $pedido->fecha_entrega?->toDateString(),
            $pedido->prioridad->value,
            $pedido->estado->value,
        );
    }
}
