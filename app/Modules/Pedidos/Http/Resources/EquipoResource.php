<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Resources;

use App\Modules\Pedidos\Application\Equipos\ListarEquiposQuery;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Adjuntos\Http\AdjuntoResource;
use App\Shared\Concurrencia\VersionDeFila;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Equipo` (pedidos.md §11.2, W-49). Cargar antes `ListarEquiposQuery::CON` (sin N+1). `updated_at` es la
 * versión para el bloqueo optimista de la edición.
 *
 * @property EquipoReparacion $resource
 */
final class EquipoResource extends JsonResource
{
    public const CON = ListarEquiposQuery::CON;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $equipo = $this->resource;

        return [
            'id' => $equipo->id,
            'equipo' => $equipo->equipo,
            'codigo' => $equipo->codigo,
            'trabajo' => $equipo->trabajo,
            'taller' => $equipo->taller,
            'estado' => $equipo->estado->value,
            'aprobacion' => $equipo->aprobacion->value,
            'fecha' => $equipo->fecha?->toDateString(),
            'notas' => $equipo->notas,
            'inventario' => $equipo->inventario === null ? null : (new AdjuntoResource($equipo->inventario))->resolve($request),
            'presupuesto' => $equipo->presupuesto === null ? null : (new AdjuntoResource($equipo->presupuesto))->resolve($request),
            'updated_at' => VersionDeFila::iso($equipo->updated_at),
            'updated_by' => ['id' => $equipo->actualizadoPor->id, 'nombre' => $equipo->actualizadoPor->nombre],
        ];
    }
}
