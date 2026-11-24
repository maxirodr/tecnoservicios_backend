<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Resources;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Application\Lecturas\GastoLeido;
use App\Modules\Gastos\Application\Lecturas\LecturaDeGasto;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Cuit;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\Http\AdjuntoResource;
use App\Shared\Concurrencia\VersionDeFila;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `GastoDetalle` = GastoFila + cabecera, adjuntos, renglones (los que ve), reparto por obra, cuántos no ve,
 * quién lo cargó y lo corrigió, la anulación y `puede` (gastos.md §12.2, W-34). Cargar con
 * `LecturaDeGasto::DETALLE`.
 *
 * @property GastoLeido $resource
 */
final class GastoDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::detalle($this->resource, app(Rubros::class));
    }

    /**
     * @return array<string, mixed>
     */
    public static function detalle(GastoLeido $leido, Rubros $rubros): array
    {
        $gasto = $leido->gasto;
        $proveedor = $gasto->proveedor;

        return [
            ...GastoFilaResource::fila($leido, $rubros),
            'numero' => $gasto->numero,
            'rendido_por' => $gasto->rendido_por,
            'periodo_desde' => $gasto->periodo_desde?->toDateString(),
            'periodo_hasta' => $gasto->periodo_hasta?->toDateString(),
            'proveedor' => [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre,
                'cuit' => $proveedor->cuit === null ? null : Cuit::formatear($proveedor->cuit),
                'es_generico' => $proveedor->es_generico,
            ],
            'adjuntos' => array_values($gasto->adjuntos->map(static fn (Adjunto $a): array => (new AdjuntoResource($a))->resolve())->all()),
            'renglones' => array_map(static fn (GastoRenglon $r): array => self::renglon($r, $rubros), $leido->renglones),
            'por_obra' => self::porObra($leido),
            'renglones_ocultos' => $leido->ocultos,
            'cargado_at' => FechaEmpresa::instante($gasto->created_at),
            'updated_at' => VersionDeFila::iso($gasto->updated_at),
            'updated_by' => self::usuario($gasto->editadoPor),
            'anulacion' => $gasto->anulado_at === null ? null : [
                'anulado_at' => FechaEmpresa::instante($gasto->anulado_at),
                'anulado_por' => self::usuario($gasto->anuladoPor),
                'motivo' => $gasto->motivo_anulacion,
            ],
            'puede' => $leido->puede,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function renglon(GastoRenglon $r, Rubros $rubros): array
    {
        $rubro = $rubros->porId($r->rubro_id);

        return [
            'id' => $r->id,
            'orden' => $r->orden,
            'fecha' => $r->fecha->toDateString(),
            'concepto' => $r->concepto,
            'cantidad' => $r->cantidad,
            'precio_unitario' => $r->precio_unitario,
            'total' => $r->total,
            'obra' => $r->obra === null ? null : [
                'id' => $r->obra->id,
                'numero' => $r->obra->principal->numero ?? '',
                'nombre_trabajo' => $r->obra->nombre_trabajo,
                'estado' => $r->obra->estado->value,
            ],
            'rubro' => $rubro === null ? null : ['clave' => $rubro->clave, 'nombre' => $rubro->nombre],
            'material' => $r->material === null ? null : ['id' => $r->material->id, 'nombre' => $r->material->nombre, 'unidad' => $r->material->unidad],
            'detalle' => $r->detalle,
        ];
    }

    /**
     * @return list<array{obra: array{id: int, nombre_trabajo: string}|null, total: string}>
     */
    private static function porObra(GastoLeido $leido): array
    {
        $grupos = [];

        foreach ($leido->renglones as $r) {
            $grupos[$r->obra_id ?? 0] ??= ['obra' => $r->obra === null ? null : ['id' => $r->obra->id, 'nombre_trabajo' => $r->obra->nombre_trabajo], 'renglones' => []];
            $grupos[$r->obra_id ?? 0]['renglones'][] = $r;
        }

        return array_values(array_map(static fn (array $g): array => ['obra' => $g['obra'], 'total' => LecturaDeGasto::suma($g['renglones'])], $grupos));
    }

    /**
     * @return array{id: int, nombre: string}|null
     */
    private static function usuario(?User $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'nombre' => $user->nombre];
    }
}
