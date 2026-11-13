<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `Adjunto` (obras.md §10.5). Nunca expone `disco` ni `ruta`. Los módulos lo reusan para mostrar
 * los adjuntos de su recurso (cargar `subidoPor` con `with` para evitar N+1).
 *
 * @mixin Adjunto
 */
final class AdjuntoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Adjunto $adjunto */
        $adjunto = $this->resource;

        return [
            'id' => $adjunto->id,
            'contexto' => $adjunto->contexto,
            'tipo' => $adjunto->tipo->value,
            'nombre' => $adjunto->nombre,
            'mime' => $adjunto->mime,
            'tamano_bytes' => $adjunto->tamano_bytes,
            'url' => $adjunto->url,
            'descarga' => $adjunto->esArchivo() ? '/api/adjuntos/'.$adjunto->id : null,
            'subido_por' => $adjunto->relationLoaded('subidoPor') ? self::usuario($adjunto->subidoPor) : null,
            'created_at' => $adjunto->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, nombre: string}
     */
    private static function usuario(User $user): array
    {
        return ['id' => $user->id, 'nombre' => $user->nombre];
    }
}
