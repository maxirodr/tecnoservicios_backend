<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain;

/**
 * N.º de factura o de remito (RN-GAS-07, resuelta DU-G10): sin espacios al borde y en mayúsculas; si tiene
 * la forma punto de venta–número ("3-12345", "0003-00012345", "00003 00012345"), con el punto de venta de
 * al menos 4 dígitos y el número de 8. Si no, tal cual.
 */
final class NumeroComprobante
{
    private const DIGITOS_PUNTO_DE_VENTA = 4;

    private const DIGITOS_NUMERO = 8;

    private const FORMA = '/^(\d{1,5})\s*[-\s]\s*(\d{1,8})$/';

    public static function normalizar(string $escrito): string
    {
        $numero = mb_strtoupper(trim($escrito));

        if (preg_match(self::FORMA, $numero, $partes) !== 1) {
            return $numero;
        }

        return str_pad($partes[1], self::DIGITOS_PUNTO_DE_VENTA, '0', STR_PAD_LEFT)
            .'-'.str_pad($partes[2], self::DIGITOS_NUMERO, '0', STR_PAD_LEFT);
    }

    /**
     * Forma de búsqueda (RN-GAS-44, resuelta DU-G07): cada tramo sin ceros a la izquierda y sin guiones
     * ni espacios. "0003-00012345", "3-12345" y "0003 00012345" dan "312345"; "12345" es parte de eso.
     */
    public static function paraBuscar(string $texto): string
    {
        $tramos = preg_split('/[^0-9A-Z]+/', mb_strtoupper(trim($texto)), -1, PREG_SPLIT_NO_EMPTY);

        return implode('', array_map(
            static fn (string $tramo): string => ctype_digit($tramo) ? (ltrim($tramo, '0') === '' ? '0' : ltrim($tramo, '0')) : $tramo,
            $tramos === false ? [] : $tramos,
        ));
    }

    public static function digitos(string $texto): int
    {
        return (int) preg_match_all('/\d/', $texto);
    }
}
