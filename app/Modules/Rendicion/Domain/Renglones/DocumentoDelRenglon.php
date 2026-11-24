<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

use App\Modules\Gastos\Contracts\RenglonImputado;
use Carbon\CarbonImmutable;

/**
 * Columna "Documento" de W-11 y W-36 (RN-REN-29, resuelta DU-R07): las mismas etiquetas que W-33 y W-34
 * (`gastos.md` RN-GAS-29). Proveedor: el nombre, o null ("—") si es el genérico de los gastos sin comprobante.
 */
final class DocumentoDelRenglon
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public static function etiqueta(RenglonImputado $renglon): string
    {
        return match ($renglon->origen) {
            'factura' => trim('Factura '.($renglon->numero ?? '')),
            'consumo_stock' => 'Consumo de stock',
            'amortizacion' => 'Amortización',
            'retiro_remito' => $renglon->numero === null ? 'Retiro' : "Retiro · remito {$renglon->numero}",
            'devolucion' => 'Devolución',
            'monto_periodo' => self::periodo($renglon->periodoDesde ?? $renglon->fecha, $renglon->periodoHasta ?? $renglon->fecha),
            'saldo_inicial' => 'Carga inicial',
            default => 'Sin documento',
        };
    }

    public static function proveedor(RenglonImputado $renglon): ?string
    {
        return $renglon->proveedorGenerico ? null : $renglon->proveedorNombre;
    }

    /** "Período 1 al 15-oct"; si cruza de mes, "Período 25-oct al 10-nov". */
    private static function periodo(string $desde, string $hasta): string
    {
        $inicio = CarbonImmutable::parse($desde);
        $fin = CarbonImmutable::parse($hasta);
        $mismoMes = $inicio->format('Y-m') === $fin->format('Y-m');

        return 'Período '.($mismoMes ? $inicio->day : self::diaYMes($inicio)).' al '.self::diaYMes($fin);
    }

    private static function diaYMes(CarbonImmutable $fecha): string
    {
        return $fecha->day.'-'.self::MESES[$fecha->month - 1];
    }
}
