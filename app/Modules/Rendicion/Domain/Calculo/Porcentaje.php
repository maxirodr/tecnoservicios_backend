<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

/**
 * Porcentajes de la rendición (rendicion.md §4.3): sobre los importes exactos, con bcmath escala 6, y
 * redondeados a 2 decimales **mitad para arriba en valor absoluto** ("54.04"). Con divisor 0: null ("—").
 */
final class Porcentaje
{
    private const ESCALA_CALCULO = 6;

    private const ESCALA = 2;

    private const MEDIO_CENTESIMO = '0.005';

    /**
     * @param  numeric-string  $parte
     * @param  numeric-string  $base
     * @return numeric-string|null
     */
    public static function de(string $parte, string $base): ?string
    {
        if (bccomp($base, '0', self::ESCALA_CALCULO) === 0) {
            return null;
        }

        $exacto = bcdiv(bcmul($parte, '100', self::ESCALA_CALCULO), $base, self::ESCALA_CALCULO);

        return self::redondear($exacto);
    }

    /**
     * @param  numeric-string  $valor
     * @return numeric-string
     */
    public static function redondear(string $valor): string
    {
        $negativo = bccomp($valor, '0', self::ESCALA_CALCULO) < 0;
        $corrido = $negativo
            ? bcsub($valor, self::MEDIO_CENTESIMO, self::ESCALA_CALCULO)
            : bcadd($valor, self::MEDIO_CENTESIMO, self::ESCALA_CALCULO);
        $redondeado = bcadd($corrido, '0', self::ESCALA);

        // "-0.00" no existe: un porcentaje que redondea a cero es cero.
        return bccomp($redondeado, '0', self::ESCALA) === 0 ? bcadd('0', '0', self::ESCALA) : $redondeado;
    }
}
