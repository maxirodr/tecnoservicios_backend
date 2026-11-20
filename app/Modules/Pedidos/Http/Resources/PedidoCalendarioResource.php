<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un pedido en el calendario (W-03, §11.1): el día de la entrega con el color de su prioridad, la barra del
 * período y el estado (completada = tachado). En "Mi gente" el front muestra el propietario. Cargar antes
 * `CalendarioDePedidos::CON`.
 *
 * @property Pedido $resource
 */
final class PedidoCalendarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pedido = $this->resource;

        return [
            'id' => $pedido->id,
            'tarea' => $pedido->tarea,
            'obra' => $pedido->obra === null ? null : ['id' => $pedido->obra->id, 'nombre_trabajo' => $pedido->obra->nombre_trabajo],
            'propietario' => $pedido->propietario === null ? null : [
                'id' => $pedido->propietario->id,
                'nombre' => $pedido->propietario->nombre,
                'activo' => $pedido->propietario->activo,
            ],
            'sector' => ['id' => $pedido->sector->id, 'nombre' => $pedido->sector->nombre],
            'prioridad' => $pedido->prioridad->value,
            'estado' => $pedido->estado->value,
            'fecha_entrega' => $pedido->fecha_entrega?->toDateString(),
            'periodo_inicio' => $pedido->periodo_inicio?->toDateString(),
            'periodo_fin' => $pedido->periodo_fin?->toDateString(),
        ];
    }
}
