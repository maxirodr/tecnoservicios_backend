<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Resources;

use App\Modules\Identidad\Domain\Models\Plazos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * W-50: `{urgente_hasta_dias, prioritario_hasta_dias, updated_at, updated_by: {id, nombre}|null}`.
 *
 * @property Plazos $resource
 */
final class PlazosResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $editor = $this->resource->editor;

        return [
            'urgente_hasta_dias' => $this->resource->urgente_hasta_dias,
            'prioritario_hasta_dias' => $this->resource->prioritario_hasta_dias,
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            'updated_by' => $editor === null ? null : ['id' => $editor->id, 'nombre' => $editor->nombre],
        ];
    }
}
