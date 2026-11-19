<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `PedidoFila` (pedidos.md §11.1). Cargar antes `PedidoFilaResource::CON` (sin N+1). "Vencido" y
 * "mañana" en la hora de la empresa (RN-PED-36).
 *
 * @property Pedido $resource
 */
final class PedidoFilaResource extends JsonResource
{
    public const CON = ['obra.principal', 'sector', 'propietario', 'solicitante'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::fila($this->resource);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fila(Pedido $pedido): array
    {
        $obra = $pedido->obra;

        return [
            'id' => $pedido->id,
            'tarea' => $pedido->tarea,
            'periodo' => $pedido->periodo_inicio === null ? null : [
                'inicio' => $pedido->periodo_inicio->toDateString(),
                'fin' => $pedido->periodo_fin?->toDateString(),
            ],
            'obra' => $obra === null ? null : [
                'id' => $obra->id,
                'numero' => $obra->principal->numero ?? null,
                'nombre_trabajo' => $obra->nombre_trabajo,
                'estado' => $obra->estado->value,
            ],
            'sector' => ['id' => $pedido->sector->id, 'nombre' => $pedido->sector->nombre],
            'propietario' => $pedido->propietario === null ? null : [
                'id' => $pedido->propietario->id,
                'nombre' => $pedido->propietario->nombre,
                'activo' => $pedido->propietario->activo,
            ],
            'solicitante' => $pedido->solicitante === null ? null : self::persona($pedido->solicitante),
            'con_quien' => $pedido->con_quien,
            'fecha_pedido' => $pedido->fecha_pedido?->toDateString(),
            'fecha_entrega' => $pedido->fecha_entrega?->toDateString(),
            'prioridad' => $pedido->prioridad->value,
            'prioridad_manual' => $pedido->prioridad_manual,
            'estado' => $pedido->estado->value,
            'vencido' => $pedido->vencidoAl(FechaEmpresa::hoyTexto()),
            'vence_manana' => $pedido->venceElDia(FechaEmpresa::mananaTexto()),
            'carga_inicial' => $pedido->carga_inicial,
        ];
    }

    /**
     * @return array{id: int, nombre: string}
     */
    public static function persona(User $user): array
    {
        return ['id' => $user->id, 'nombre' => $user->nombre];
    }
}
