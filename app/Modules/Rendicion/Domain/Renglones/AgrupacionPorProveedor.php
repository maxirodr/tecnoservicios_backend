<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\TextoComparable;

/**
 * W-37 (RN-REN-34…36, resuelta DU-R07): por proveedor y, adentro, por concepto (sin distinguir mayúsculas,
 * tildes ni espacios dobles). Proveedores y conceptos por total, mayor primero; el grupo "Sin documento" (los
 * renglones del proveedor genérico) siempre al final. Comprobantes = facturas distintas (un remito no cuenta).
 */
final class AgrupacionPorProveedor
{
    private const SIN_DOCUMENTO = 'sin_documento';

    private const ORIGEN_FACTURA = 'factura';

    /**
     * @param  list<RenglonImputado>  $renglones
     * @return list<GrupoDeProveedor>
     */
    public static function agrupar(array $renglones): array
    {
        /** @var array<string, list<RenglonImputado>> $porGrupo */
        $porGrupo = [];

        foreach ($renglones as $renglon) {
            $clave = $renglon->proveedorGenerico || $renglon->proveedorId === null ? self::SIN_DOCUMENTO : (string) $renglon->proveedorId;
            $porGrupo[$clave][] = $renglon;
        }

        $grupos = [];
        $sinDocumento = null;

        foreach ($porGrupo as $clave => $delGrupo) {
            $grupo = self::grupo($clave === self::SIN_DOCUMENTO, $delGrupo);
            $clave === self::SIN_DOCUMENTO ? $sinDocumento = $grupo : $grupos[] = $grupo;
        }

        usort($grupos, static fn (GrupoDeProveedor $a, GrupoDeProveedor $b): int => bccomp($b->total, $a->total, Decimal::IMPORTE)
            ?: strcmp(TextoComparable::de((string) $a->proveedorNombre), TextoComparable::de((string) $b->proveedorNombre)));

        return $sinDocumento === null ? $grupos : [...$grupos, $sinDocumento];
    }

    /**
     * Facturas distintas del conjunto (el pie: "4 comprobantes").
     *
     * @param  list<RenglonImputado>  $renglones
     */
    public static function comprobantes(array $renglones): int
    {
        $facturas = [];

        foreach ($renglones as $renglon) {
            if ($renglon->origen === self::ORIGEN_FACTURA) {
                $facturas[$renglon->gastoId] = true;
            }
        }

        return count($facturas);
    }

    /**
     * @param  list<RenglonImputado>  $renglones
     */
    private static function grupo(bool $sinDocumento, array $renglones): GrupoDeProveedor
    {
        $comprobantes = self::comprobantes($renglones);
        $primero = $renglones[0] ?? null;

        return new GrupoDeProveedor(
            $sinDocumento ? null : $primero?->proveedorId,
            $sinDocumento ? null : $primero?->proveedorNombre,
            $sinDocumento,
            Decimal::suma(array_map(static fn (RenglonImputado $r): string => Decimal::suma([$r->total]), $renglones)),
            $sinDocumento || $comprobantes === 0 ? null : $comprobantes,
            self::conceptos($renglones),
        );
    }

    /**
     * @param  list<RenglonImputado>  $renglones
     * @return list<ConceptoAgrupado>
     */
    private static function conceptos(array $renglones): array
    {
        /** @var array<string, array{concepto: string, cantidad: numeric-string, total: numeric-string}> $porConcepto */
        $porConcepto = [];

        foreach ($renglones as $renglon) {
            $clave = TextoComparable::de($renglon->concepto);
            $actual = $porConcepto[$clave] ?? ['concepto' => $renglon->concepto, 'cantidad' => '0', 'total' => '0'];
            $porConcepto[$clave] = [
                'concepto' => $actual['concepto'],
                'cantidad' => Decimal::suma([$actual['cantidad'], Decimal::suma([$renglon->cantidad], Decimal::CANTIDAD)], Decimal::CANTIDAD),
                'total' => Decimal::suma([$actual['total'], Decimal::suma([$renglon->total])]),
            ];
        }

        $conceptos = array_values(array_map(
            static fn (array $c): ConceptoAgrupado => new ConceptoAgrupado($c['concepto'], $c['cantidad'], $c['total']),
            $porConcepto,
        ));

        usort($conceptos, static fn (ConceptoAgrupado $a, ConceptoAgrupado $b): int => bccomp($b->total, $a->total, Decimal::IMPORTE)
            ?: strcmp(TextoComparable::de($a->concepto), TextoComparable::de($b->concepto)));

        return $conceptos;
    }
}
