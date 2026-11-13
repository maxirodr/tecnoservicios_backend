<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Resources;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Obras\FilaObra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ObraFila (W-05, obras.md §10.1). "Pedidos abiertos" y "% gastado" en null = "—" (RN-OBR-03, 04).
 *
 * @property FilaObra $resource
 */
final class ObraFilaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fila = $this->resource;
        $obra = $fila->obra;
        $principal = $obra->principal;

        return [
            'id' => $obra->id,
            'numero_presupuesto' => $principal?->numero,
            'revision' => $principal?->revision,
            'adicionales' => (int) $obra->getAttribute('adicionales_count'),
            'nombre_trabajo' => $obra->nombre_trabajo,
            'cliente' => $obra->cliente,
            'planta' => $obra->planta,
            'responsable' => [...self::usuario($obra->responsable), 'activo' => $obra->responsable->activo],
            'estado' => $obra->estado->value,
            'pedidos_abiertos' => $fila->pedidosAbiertos,
            'porcentaje_gastado' => $fila->porcentajeGastado,
            'pasado_de_presupuesto' => $fila->pasadoDePresupuesto,
        ];
    }

    /**
     * @return array{id: int, nombre: string}
     */
    public static function usuario(User $user): array
    {
        return ['id' => $user->id, 'nombre' => $user->nombre];
    }
}
