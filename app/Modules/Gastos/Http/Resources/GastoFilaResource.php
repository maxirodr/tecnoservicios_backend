<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Resources;

use App\Modules\Gastos\Application\Lecturas\GastoLeido;
use App\Modules\Gastos\Application\Rubros;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `GastoFila` (W-33, gastos.md §12.2): documento con la etiqueta común con la rendición, proveedor (el
 * front muestra "—" si es el genérico), obras y rubros de los renglones que ve (null = sin obra), total y,
 * con filtro de obra o rubro o alcance propio, `total_filtrado`. Importes como string decimal.
 *
 * @property GastoLeido $resource
 */
final class GastoFilaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::fila($this->resource, app(Rubros::class));
    }

    /**
     * @param  numeric-string|null  $parte  en un grupo, la parte que le toca (RN-GAS-33)
     * @return array<string, mixed>
     */
    public static function fila(GastoLeido $leido, Rubros $rubros, ?string $parte = null): array
    {
        $gasto = $leido->gasto;
        $obras = [];
        $claves = [];

        foreach ($leido->renglones as $renglon) {
            $obras[$renglon->obra_id ?? 0] ??= $renglon->obra === null ? null : ['id' => $renglon->obra->id, 'nombre_trabajo' => $renglon->obra->nombre_trabajo];
            $clave = $rubros->porId($renglon->rubro_id)?->clave;

            if ($clave !== null) {
                $claves[$clave] = $clave;
            }
        }

        $totalFiltrado = $parte ?? $leido->totalFiltrado;

        return [
            'id' => $gasto->id,
            'fecha' => $gasto->fecha->toDateString(),
            'tipo_documento' => $gasto->tipo_documento->value,
            'origen' => $gasto->origen->value,
            'documento' => $leido->documento,
            'proveedor' => ['id' => $gasto->proveedor->id, 'nombre' => $gasto->proveedor->nombre, 'es_generico' => $gasto->proveedor->es_generico],
            'obras' => array_values($obras),
            'rubros' => array_values($claves),
            'total' => $leido->total,
            ...($totalFiltrado === null ? [] : ['total_filtrado' => $totalFiltrado]),
            'cargado_por' => ['id' => $gasto->cargadoPor->id, 'nombre' => $gasto->cargadoPor->nombre],
            'anulado' => $gasto->anulado(),
        ];
    }
}
