<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain;

use App\Modules\Gastos\Domain\Enums\Origen;
use DateTimeInterface;

/**
 * Columna "Documento" con las mismas etiquetas que la rendición (RN-GAS-29, RN-REN-29, resuelta DU-R07):
 * "Factura 0003-00012345" · "Sin documento" · "Consumo de stock" · "Amortización" · "Retiro · remito
 * 0001-00000456" · "Devolución" · "Período 1 al 15-oct" · "Carga inicial".
 */
final class EtiquetaDocumento
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public static function de(Origen $origen, ?string $numero, ?DateTimeInterface $desde, ?DateTimeInterface $hasta): string
    {
        return match ($origen) {
            Origen::Factura => trim('Factura '.($numero ?? '')),
            Origen::SinComprobante, Origen::Combustible => 'Sin documento',
            Origen::ConsumoStock => 'Consumo de stock',
            Origen::Amortizacion => 'Amortización',
            Origen::RetiroRemito => trim('Retiro · remito '.($numero ?? '')),
            Origen::Devolucion => 'Devolución',
            Origen::MontoPeriodo => 'Período '.self::periodo($desde, $hasta),
            Origen::SaldoInicial => 'Carga inicial',
        };
    }

    /** "1 al 15-oct"; si cambia el mes, "20-sep al 5-oct". */
    public static function periodo(?DateTimeInterface $desde, ?DateTimeInterface $hasta): string
    {
        if ($desde === null || $hasta === null) {
            return '';
        }

        $mismoMes = $desde->format('Y-m') === $hasta->format('Y-m');

        return ($mismoMes ? $desde->format('j') : self::diaYMes($desde)).' al '.self::diaYMes($hasta);
    }

    /** "17-oct" */
    public static function diaYMes(DateTimeInterface $fecha): string
    {
        return $fecha->format('j').'-'.self::MESES[(int) $fecha->format('n') - 1];
    }
}
