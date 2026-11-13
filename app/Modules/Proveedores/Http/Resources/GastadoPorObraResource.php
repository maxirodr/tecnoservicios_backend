<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Resources;

use App\Modules\Proveedores\Application\Ports\FilaGastado;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * "Gastado por obra" (proveedores.md §10.3): `{filas: [{obra|null, renglones, importe}], total}`.
 * Importes como string decimal (ADR-008).
 *
 * @property GastadoPorObra $resource
 */
final class GastadoPorObraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gastado = $this->resource;

        return [
            'filas' => array_map(static fn (FilaGastado $fila): array => [
                'obra' => $fila->obra === null ? null : [
                    'id' => $fila->obra->id,
                    'numero' => $fila->obra->numero,
                    'nombre_trabajo' => $fila->obra->nombreTrabajo,
                ],
                'renglones' => $fila->renglones,
                'importe' => $fila->importe,
            ], $gastado->filas),
            'total' => ['renglones' => $gastado->totalRenglones(), 'importe' => $gastado->totalImporte()],
        ];
    }
}
