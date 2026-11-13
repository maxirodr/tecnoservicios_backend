<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Resources;

use App\Modules\Obras\Application\Obras\FichaObra;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ObraFicha = ObraFila + fechas, presupuestos, contactos, documentos y `puede` (obras.md §10.1).
 *
 * @property FichaObra $resource
 */
final class ObraFichaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ficha = $this->resource;
        $obra = $ficha->fila->obra;
        $conMontoObra = $ficha->puede['ver_monto_obra'];
        $contactos = [];

        foreach ($obra->contactos as $contacto) {
            /** @var ObraContacto $contacto */
            $contactos[$contacto->tipo->value] = ['nombre' => $contacto->nombre, 'email' => $contacto->email, 'telefono' => $contacto->telefono];
        }

        return [
            ...(new ObraFilaResource($ficha->fila))->toArray($request),
            'fecha_inicio_prevista' => $obra->fecha_inicio_prevista?->toDateString(),
            'fecha_fin_prevista' => $obra->fecha_fin_prevista?->toDateString(),
            'carpeta_drive_url' => $obra->carpeta_drive_url,
            'terminada_at' => $obra->terminada_at?->format(DATE_ATOM),
            'terminada_por' => $obra->terminadaPor === null ? null : ObraFilaResource::usuario($obra->terminadaPor),
            'created_at' => $obra->created_at->toIso8601String(),
            'created_by' => ObraFilaResource::usuario($obra->creadaPor),
            'presupuestos' => $obra->presupuestos->map(static fn (Presupuesto $p): array => (new PresupuestoResource($p, $conMontoObra))->toArray($request))->values()->all(),
            'contactos' => $contactos,
            'documentos' => $obra->documentos->map(static fn (ObraDocumento $d): array => (new DocumentoResource($d))->toArray($request))->values()->all(),
            'puede' => $ficha->puede,
        ];
    }
}
