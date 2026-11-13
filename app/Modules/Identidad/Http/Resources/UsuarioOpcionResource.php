<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Opción de desplegable: `{id, nombre, sector: {id, nombre}}` (sin mail ni datos personales).
 *
 * @property User $resource
 */
final class UsuarioOpcionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nombre' => $this->resource->nombre,
            'sector' => ['id' => $this->resource->sector->id, 'nombre' => $this->resource->sector->nombre],
        ];
    }
}
