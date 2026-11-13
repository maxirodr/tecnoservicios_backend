<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Resources;

use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\Http\AdjuntoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Presupuesto (W-20, obras.md §10.1). `monto_obra` solo viaja a quien tiene `monto_obra ≥ ver` para
 * esa obra; para el resto la clave no existe (RN-OBR-36). Necesita `adjuntos.subidoPor` y `creadoPor`.
 *
 * @property Presupuesto $resource
 */
final class PresupuestoResource extends JsonResource
{
    public function __construct(Presupuesto $resource, private readonly bool $conMontoObra)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $presupuesto = $this->resource;
        $aprobacion = $presupuesto->adjuntoAprobacion();
        $tieneAprobacion = $presupuesto->aprobacion_tipo !== null || $aprobacion !== null;

        return [
            'id' => $presupuesto->id,
            'numero' => $presupuesto->numero,
            'revision' => $presupuesto->revision,
            'es_principal' => $presupuesto->es_principal,
            'descripcion' => $presupuesto->descripcion,
            'oferta' => self::adjunto($presupuesto->oferta(), $request),
            'aprobacion' => $tieneAprobacion ? [
                'tipo' => $presupuesto->aprobacion_tipo?->value,
                'etiqueta' => $presupuesto->aprobacion_tipo?->etiqueta(),
                'numero' => $presupuesto->aprobacion_numero,
                'adjunto' => self::adjunto($aprobacion, $request),
            ] : null,
            'modo_carga' => $presupuesto->modo_carga->value,
            'created_at' => $presupuesto->created_at->toDateString(),
            'created_by' => ObraFilaResource::usuario($presupuesto->creadoPor),
            ...($this->conMontoObra ? ['monto_obra' => $presupuesto->monto_obra] : []),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function adjunto(?Adjunto $adjunto, Request $request): ?array
    {
        return $adjunto === null ? null : (new AdjuntoResource($adjunto))->toArray($request);
    }
}
