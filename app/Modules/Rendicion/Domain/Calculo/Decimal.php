<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

use InvalidArgumentException;

/**
 * Sumas exactas al centavo (importes, escala 2) y al milésimo (cantidades, escala 3), sin float (ADR-008).
 * La rendición no redondea ningún total: solo suma y resta (rendicion.md §4.3). Los importes que llegan de
 * otros módulos (string decimal) se verifican acá: uno que no es número es un error del contrato.
 */
final class Decimal
{
    public const IMPORTE = 2;

    public const CANTIDAD = 3;

    /**
     * @return numeric-string
     *
     * @throws InvalidArgumentException si no es un número
     */
    public static function numero(string $valor): string
    {
        if (! is_numeric($valor)) {
            throw new InvalidArgumentException("No es un importe ni una cantidad: {$valor}");
        }

        return $valor;
    }

    /**
     * @param  iterable<string>  $valores
     * @return numeric-string
     */
    public static function suma(iterable $valores, int $escala = self::IMPORTE): string
    {
        $total = bcadd('0', '0', $escala);

        foreach ($valores as $valor) {
            $total = bcadd($total, self::numero($valor), $escala);
        }

        return $total;
    }

    /**
     * @return numeric-string
     */
    public static function resta(string $a, string $b, int $escala = self::IMPORTE): string
    {
        return bcsub(self::numero($a), self::numero($b), $escala);
    }

    /** -1, 0 o 1, como `bccomp`. */
    public static function comparar(string $a, string $b, int $escala = self::IMPORTE): int
    {
        return bccomp(self::numero($a), self::numero($b), $escala);
    }

    public static function mayor(string $a, string $b, int $escala = self::IMPORTE): bool
    {
        return self::comparar($a, $b, $escala) > 0;
    }

    public static function esCero(string $a, int $escala = self::IMPORTE): bool
    {
        return self::comparar($a, '0', $escala) === 0;
    }
}
