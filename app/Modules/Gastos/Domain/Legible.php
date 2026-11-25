<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain;

use App\Shared\Domain\Importe;
use DateTimeInterface;

/**
 * Cómo lee los números la persona en los textos que arma el backend (historial, avisos de repetidos):
 * formato es-AR sin ",00" (gastos.md §4.5): "9.800", "9.800,50", "−39.200"; cantidades sin ceros de más
 * ("30", "2,5"). En el resto de la API viajan crudos (string decimal, ADR-008).
 */
final class Legible
{
    /** El signo menos tipográfico (U+2212), no el guion. */
    private const MENOS = '−';

    /**
     * @param  numeric-string  $importe
     */
    public static function importe(string $importe): string
    {
        $texto = Importe::desde($importe)->legible();

        return str_starts_with($texto, '-') ? self::MENOS.substr($texto, 1) : $texto;
    }

    /**
     * @param  numeric-string  $cantidad
     */
    public static function cantidad(string $cantidad): string
    {
        $negativo = str_starts_with($cantidad, '-');
        [$enteros, $decimales] = array_pad(explode('.', ltrim($cantidad, '-')), 2, '');
        $conPuntos = (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $enteros);
        $decimales = rtrim($decimales, '0');
        $texto = $decimales === '' ? $conPuntos : "{$conPuntos},{$decimales}";

        return $negativo && $texto !== '0' ? self::MENOS.$texto : $texto;
    }

    /** "17-10-2026" */
    public static function fecha(?DateTimeInterface $fecha): string
    {
        return $fecha === null ? '—' : $fecha->format('d-m-Y');
    }
}
