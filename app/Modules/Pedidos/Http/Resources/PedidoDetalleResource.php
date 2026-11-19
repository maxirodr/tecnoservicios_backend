<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Consultas\DetallePedido;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\PedidoComentario;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\Http\AdjuntoResource;
use App\Shared\Concurrencia\VersionDeFila;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `PedidoDetalle` = PedidoFila + aclaraciones, bloqueo, cierre, adjuntos, comprobantes, seguidores,
 * comentarios y `puede` (pedidos.md §11.1). Al crear y al reasignar suma `avisados` (RN-PED-08).
 *
 * @property DetallePedido $resource
 */
final class PedidoDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $detalle = $this->resource;
        $pedido = $detalle->pedido;
        $fila = PedidoFilaResource::fila($pedido);

        if ($pedido->solicitante !== null) {
            $fila['solicitante']['sector'] = self::sector($pedido->solicitante);
        }

        if ($pedido->propietario !== null) {
            $fila['propietario']['sector'] = self::sector($pedido->propietario);
        }

        $datos = [
            ...$fila,
            'aclaraciones' => $pedido->aclaraciones,
            'motivo_bloqueo' => $pedido->motivo_bloqueo,
            'dias_anticipacion' => $detalle->diasAnticipacion,
            'prioridad_sugerida' => $pedido->prioridad_manual && $detalle->calculada !== null
                ? ['prioridad' => $detalle->calculada->value, 'dias' => $detalle->diasAnticipacion]
                : null,
            'adjuntos' => $pedido->adjuntos->map(static fn (Adjunto $a): array => (new AdjuntoResource($a))->toArray($request))->values()->all(),
            'comprobantes' => $pedido->comprobantes->map(static fn (Adjunto $a): array => (new AdjuntoResource($a))->toArray($request))->values()->all(),
            'cierre' => $pedido->completado_at === null ? null : [
                'comentario' => $pedido->comentario_cierre,
                'completado_at' => FechaEmpresa::instante($pedido->completado_at),
                'completado_por' => $pedido->completadoPor === null ? null : PedidoFilaResource::persona($pedido->completadoPor),
            ],
            'seguidores' => $pedido->seguidores->map(static fn (User $u): array => PedidoFilaResource::persona($u))->values()->all(),
            'sigo' => $detalle->sigo,
            'comentarios' => $pedido->comentarios->map(static fn (PedidoComentario $c): array => ComentarioResource::comentario($c))->values()->all(),
            'created_at' => FechaEmpresa::instante($pedido->created_at),
            // Versión para el bloqueo optimista de Editar (PU-002).
            'updated_at' => VersionDeFila::iso($pedido->updated_at),
            'puede' => $detalle->puede,
        ];

        if ($detalle->avisados !== null) {
            $datos['avisados'] = $detalle->avisados;
        }

        return $datos;
    }

    /**
     * @return array{id: int, nombre: string}
     */
    private static function sector(User $user): array
    {
        return ['id' => $user->sector->id, 'nombre' => $user->sector->nombre];
    }
}
