<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain;

/**
 * Total del renglón = cantidad × precio unitario, redondeado a 2 decimales mitad para arriba en valor
 * absoluto, como REDONDEAR de Excel (gastos.md §4.5, ADR-008): 0,005 → 0,01 y −0,005 → −0,01. Con bcmath,
 * nunca float. El total del gasto es la suma, sin volver a redondear.
 */
final class TotalDeRenglon
{
    public const ESCALA_IMPORTE = 2;

    public const ESCALA_CANTIDAD = 3;

    /** Cantidad (3) + precio (2) decimales: el producto es exacto con 5. */
    private const ESCALA_PRODUCTO = 5;

    private const MEDIO_CENTAVO = '0.005';

    /**
     * @param  numeric-string  $cantidad
     * @param  numeric-string  $precioUnitario
     * @return numeric-string
     */
    public static function calcular(string $cantidad, string $precioUnitario): string
    {
        $producto = bcmul($cantidad, $precioUnitario, self::ESCALA_PRODUCTO);
        $alejado = bccomp($producto, '0', self::ESCALA_PRODUCTO) < 0
            ? bcsub($producto, self::MEDIO_CENTAVO, self::ESCALA_PRODUCTO)
            : bcadd($producto, self::MEDIO_CENTAVO, self::ESCALA_PRODUCTO);

        // bcadd trunca hacia el cero: con el medio centavo sumado lejos del cero, redondea mitad para arriba.
        return self::sinMenosCero(bcadd($alejado, '0', self::ESCALA_IMPORTE));
    }

    /**
     * @param  list<numeric-string>  $totales
     * @return numeric-string
     */
    public static function suma(array $totales): string
    {
        return array_reduce(
            $totales,
            static fn (string $acumulado, string $total): string => bcadd($acumulado, $total, self::ESCALA_IMPORTE),
            bcadd('0', '0', self::ESCALA_IMPORTE),
        );
    }

    /**
     * Un decimal leído de la base (DECIMAL o SUM) con escala fija: "549000" → "549000.00".
     *
     * @return numeric-string
     */
    public static function escala(mixed $valor, int $escala = self::ESCALA_IMPORTE): string
    {
        $texto = is_scalar($valor) ? (string) $valor : '0';

        return bcadd(is_numeric($texto) ? $texto : '0', '0', $escala);
    }

    /**
     * @param  numeric-string  $valor
     * @return numeric-string
     */
    private static function sinMenosCero(string $valor): string
    {
        return bccomp($valor, '0', self::ESCALA_IMPORTE) === 0 ? bcadd('0', '0', self::ESCALA_IMPORTE) : $valor;
    }
}
