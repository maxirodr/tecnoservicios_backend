<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Estado;

use App\Modules\Obras\Application\Ports\PedidoAbiertoResumen;

/** Forma JSON de un pedido abierto (obras.md §10.1 `GET /api/obras/{id}/pedidos-abiertos`). */
final class PedidoAbiertoArray
{
    /**
     * @return array{id: int, tarea: string, propietario: array{id: int, nombre: string|null}|null, sector: array{id: int, nombre: string}, estado: string}
     */
    public static function de(PedidoAbiertoResumen $pedido): array
    {
        return [
            'id' => $pedido->id,
            'tarea' => $pedido->tarea,
            'propietario' => $pedido->propietarioId === null ? null : ['id' => $pedido->propietarioId, 'nombre' => $pedido->propietarioNombre],
            'sector' => ['id' => $pedido->sectorId, 'nombre' => $pedido->sectorNombre],
            'estado' => $pedido->estado,
        ];
    }
}
