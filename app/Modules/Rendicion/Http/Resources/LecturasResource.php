<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Resources;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Rendicion\Domain\Calculo\FilaDeRubro;
use App\Modules\Rendicion\Domain\Calculo\TotalDeObra;
use App\Modules\Rendicion\Domain\Renglones\ConceptoAgrupado;
use App\Modules\Rendicion\Domain\Renglones\GrupoDeProveedor;

/** Formas JSON de W-36 `DetalleDeRubro`, W-37 `PorProveedor` y W-12 `RendicionesEnCurso` (rendicion.md §12.1). */
final class LecturasResource
{
    /**
     * @param  array{fila: FilaDeRubro, subtotales: array<string, numeric-string>, renglones: list<RenglonImputado>}  $detalle
     * @return array<string, mixed>
     */
    public static function detalle(array $detalle): array
    {
        $fila = $detalle['fila'];
        $datos = [
            'rubro' => ['clave' => $fila->clave, 'nombre' => $fila->nombre, 'incluye' => $fila->incluye],
            'presupuestado' => $fila->presupuestado,
            'gastado' => $fila->gastado,
            'saldo' => $fila->saldo,
            'porcentaje' => $fila->porcentaje,
            'pasado' => $fila->pasado,
            'sin_presupuesto' => $fila->sinPresupuesto,
        ];

        foreach ($detalle['subtotales'] as $clave => $subtotal) {
            $datos["subtotal_{$clave}"] = $subtotal;
        }

        return [
            ...$datos,
            'cantidad_renglones' => count($detalle['renglones']),
            'renglones' => array_map(static fn (RenglonImputado $r): array => [
                ...RendicionResource::renglonBase($r),
                'cantidad' => $r->cantidad,
                // Parte de horas resumido: sin valor hora (RN-REN-63).
                'precio_unitario' => $r->resumido ? null : $r->precioUnitario,
                'total' => $r->total,
                'rubro_clave' => $r->rubroClave,
                'resumido' => $r->resumido,
            ], $detalle['renglones']),
        ];
    }

    /**
     * @param  array{grupos: list<GrupoDeProveedor>, total: numeric-string, comprobantes: int}  $porProveedor
     * @param  array{rubro: string|null, desde: string|null, hasta: string|null}  $filtros
     * @return array<string, mixed>
     */
    public static function porProveedor(array $porProveedor, array $filtros): array
    {
        return [
            'filtros' => $filtros,
            'grupos' => array_map(static fn (GrupoDeProveedor $g): array => [
                'proveedor' => $g->sinDocumento ? null : ['id' => $g->proveedorId, 'nombre' => $g->proveedorNombre],
                'sin_documento' => $g->sinDocumento,
                'total' => $g->total,
                'comprobantes' => $g->comprobantes,
                'conceptos' => array_map(static fn (ConceptoAgrupado $c): array => ['concepto' => $c->concepto, 'cantidad' => $c->cantidad, 'total' => $c->total], $g->conceptos),
            ], $porProveedor['grupos']),
            'total' => $porProveedor['total'],
            'comprobantes' => $porProveedor['comprobantes'],
            'cantidad_grupos' => count($porProveedor['grupos']),
        ];
    }

    /**
     * @param  array{filas: list<array{obra: ObraResumen, total: TotalDeObra}>, total: array{presupuestado: numeric-string, gastado: numeric-string, porcentaje: numeric-string|null, rubros_pasados: int|null}}  $enCurso
     * @return array<string, mixed>
     */
    public static function enCurso(array $enCurso): array
    {
        return [
            'obras' => array_map(static fn (array $f): array => [
                'obra' => RendicionResource::obra($f['obra']),
                'cliente' => $f['obra']->cliente,
                'presupuestado' => $f['total']->presupuestado,
                'gastado' => $f['total']->gastado,
                'porcentaje' => $f['total']->porcentaje,
                'pasado' => $f['total']->pasado,
                'rubros_pasados' => $f['total']->rubrosPasados,
            ], $enCurso['filas']),
            'total' => $enCurso['total'],
        ];
    }
}
