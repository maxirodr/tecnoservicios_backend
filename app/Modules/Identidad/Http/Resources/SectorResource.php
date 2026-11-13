<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Domain\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila de W-40: `{id, nombre, usuarios_activos, activo}` (requiere el conteo `usuarios_activos`).
 *
 * @property Sector $resource
 */
final class SectorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nombre' => $this->resource->nombre,
            'usuarios_activos' => (int) $this->resource->getAttribute('usuarios_activos'),
            'activo' => $this->resource->activo,
        ];
    }
}
