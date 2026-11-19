<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Http\Resources;

use App\Modules\Avisos\Domain\Models\Aviso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Aviso (avisos.md §9). Todo sale de la foto del momento guardada en `datos` (RN-AVI-07): si después cambia
 * el pedido, el aviso dice lo que decía. `obra` null = "Servicios generales"; `actor` null = el sistema.
 *
 * @property Aviso $resource
 */
final class AvisoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $aviso = $this->resource;
        $obra = $aviso->datos['obra'] ?? null;

        return [
            'id' => $aviso->id,
            'tipo' => $aviso->tipo->value,
            'texto' => $aviso->dato('texto'),
            'pedido' => $aviso->pedido_id !== null ? ['id' => $aviso->pedido_id, 'tarea' => $aviso->dato('tarea')] : null,
            'obra' => is_array($obra) ? ['id' => $obra['id'] ?? null, 'nombre_trabajo' => $obra['nombre_trabajo'] ?? null] : null,
            'actor' => $aviso->actor_id !== null ? ['id' => $aviso->actor_id, 'nombre' => $aviso->dato('actor_nombre')] : null,
            'leido' => $aviso->leido_at !== null,
            'created_at' => $aviso->created_at->copy()->setTimezone(config()->string('app.zona_empresa'))->toIso8601String(),
        ];
    }
}
