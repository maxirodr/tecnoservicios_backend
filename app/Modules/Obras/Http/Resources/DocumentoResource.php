<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Resources;

use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Certificado u otro documento de la obra (W-20) con sus archivos y links (sin ruta en disco).
 *
 * @property ObraDocumento $resource
 */
final class DocumentoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $documento = $this->resource;

        return [
            'id' => $documento->id,
            'tipo' => $documento->tipo->value,
            'nombre' => $documento->nombre,
            'fecha' => $documento->fecha?->toDateString(),
            'adjuntos' => $documento->adjuntos->map(static fn (Adjunto $a): ?array => PresupuestoResource::adjunto($a, $request))->values()->all(),
        ];
    }
}
