<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Resources;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Obras\Contracts\MontoDePresupuesto;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\PresupuestoDeObra;
use App\Modules\Rendicion\Application\Rendicion\RendicionDeObra;
use App\Modules\Rendicion\Domain\Calculo\FilaDeRubro;
use App\Modules\Rendicion\Domain\Calculo\MontoYGanancia;
use App\Modules\Rendicion\Domain\Calculo\ResumenDeRendicion;
use App\Modules\Rendicion\Domain\Calculo\TotalDeObra;
use App\Modules\Rendicion\Domain\Renglones\DocumentoDelRenglon;

/**
 * Forma JSON de W-11 / W-44 (rendicion.md §12.1 `RendicionDeObra`). Importes y % como string decimal; la clave
 * `monto` solo viaja con `monto_obra` ≥ ver para esa obra (RN-REN-23).
 */
final class RendicionResource
{
    /**
     * @param  array<string, string>  $nombresDeRubros  clave => nombre (los 13)
     * @return array<string, mixed>
     */
    public static function deObra(RendicionDeObra $rendicion, array $nombresDeRubros): array
    {
        $resumen = $rendicion->resumen;
        $datos = [
            'obra' => self::obra($rendicion->obra) + ['cliente' => $rendicion->obra->cliente, 'estado' => $rendicion->obra->estado],
            'actualizada_at' => $rendicion->actualizadaAt->toIso8601String(),
            ...self::resumen($resumen),
        ];

        if ($rendicion->verMonto) {
            $datos['monto'] = $rendicion->monto === null ? null : self::monto($rendicion->monto);
        }

        $datos['ultimos_gastos'] = array_map(static fn (RenglonImputado $r): array => [
            ...self::renglonBase($r),
            'rubro' => ['clave' => $r->rubroClave, 'nombre' => $nombresDeRubros[$r->rubroClave] ?? $r->rubroClave],
            'total' => $r->total,
        ], $rendicion->ultimosGastos);
        $datos['puede'] = $rendicion->puede;

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    public static function resumen(ResumenDeRendicion $resumen): array
    {
        return [
            'presupuesto' => [
                'situacion' => $resumen->situacion->value,
                'presupuestos' => array_map(static fn (PresupuestoDeObra $p): array => [
                    'id' => $p->id, 'numero' => $p->numero, 'revision' => $p->revision, 'es_principal' => $p->esPrincipal,
                    'modo_carga' => $p->modoCarga, 'total' => $p->total,
                ], $resumen->presupuestos),
                'sin_cargar' => $resumen->sinCargar,
            ],
            'rubros' => array_map(self::fila(...), $resumen->rubros),
            'sin_desglose' => $resumen->sinDesglose === null ? null : [
                'presupuestado' => $resumen->sinDesglose->presupuestado,
                'presupuestos' => $resumen->sinDesglose->presupuestos,
            ],
            'total' => self::total($resumen->total),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fila(FilaDeRubro $fila): array
    {
        return [
            'clave' => $fila->clave,
            'nombre' => $fila->nombre,
            'incluye' => $fila->incluye,
            'carga_por_periodo' => $fila->cargaPorPeriodo,
            'presupuestado' => $fila->presupuestado,
            'gastado' => $fila->gastado,
            'saldo' => $fila->saldo,
            'porcentaje' => $fila->porcentaje,
            'pasado' => $fila->pasado,
            'sin_presupuesto' => $fila->sinPresupuesto,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function total(TotalDeObra $total): array
    {
        return [
            'presupuestado' => $total->presupuestado,
            'gastado' => $total->gastado,
            'saldo' => $total->saldo,
            'porcentaje' => $total->porcentaje,
            'pasado' => $total->pasado,
            'rubros_pasados' => $total->rubrosPasados,
        ];
    }

    /**
     * @return array{id: int, numero: string, nombre_trabajo: string}
     */
    public static function obra(ObraResumen $obra): array
    {
        return ['id' => $obra->id, 'numero' => $obra->numero, 'nombre_trabajo' => $obra->nombreTrabajo];
    }

    /**
     * Lo común de un renglón en W-11, W-36: fecha efectiva, documento y proveedor ("—" = null).
     *
     * @return array<string, mixed>
     */
    public static function renglonBase(RenglonImputado $renglon): array
    {
        return [
            'renglon_id' => $renglon->renglonId,
            'gasto_id' => $renglon->gastoId,
            'fecha' => $renglon->fecha,
            'documento' => DocumentoDelRenglon::etiqueta($renglon),
            'proveedor' => DocumentoDelRenglon::proveedor($renglon),
            'concepto' => $renglon->concepto,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function monto(MontoYGanancia $monto): array
    {
        return [
            'monto_obra' => $monto->montoObra,
            'por_presupuesto' => array_map(static fn (MontoDePresupuesto $p): array => ['numero' => $p->numero, 'monto' => $p->monto], $monto->porPresupuesto),
            'sin_monto' => $monto->sinMonto,
            'ganancia_prevista' => $monto->gananciaPrevista,
            'porcentaje_prevista' => $monto->porcentajePrevista,
            'ganancia_al_dia' => $monto->gananciaAlDia,
            'porcentaje_al_dia' => $monto->porcentajeAlDia,
        ];
    }
}
