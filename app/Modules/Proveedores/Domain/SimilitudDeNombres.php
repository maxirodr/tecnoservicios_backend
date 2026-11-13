<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Domain;

/**
 * Regla de "parecidos" (RN-PRV-08) sobre nombres ya normalizados fuerte. Son parecidos si:
 * 1. son iguales, o
 * 2. uno contiene al otro (el contenido con al menos 4 letras), o
 * 3. comparten al menos la mitad de las palabras (del que tiene más), o
 * 4. se parecen al menos un 80 % letra por letra (Levenshtein ≤ 20 % del más largo).
 * El puntaje (1 = iguales) ordena "los más parecidos primero".
 */
final class SimilitudDeNombres
{
    private const LETRAS_MINIMAS_CONTENIDO = 4;

    private const PROPORCION_PALABRAS = 0.5;

    private const SIMILITUD_MINIMA = 0.8;

    private const IGUALES = 1.0;

    public static function sonParecidos(string $uno, string $otro): bool
    {
        if ($uno === '' || $otro === '') {
            return false;
        }

        return $uno === $otro
            || self::unoContieneAlOtro($uno, $otro)
            || self::compartenLaMitadDeLasPalabras($uno, $otro)
            || self::puntaje($uno, $otro) >= self::SIMILITUD_MINIMA;
    }

    /** Proporción de letras iguales: 1 - distancia de Levenshtein / largo del más largo. */
    public static function puntaje(string $uno, string $otro): float
    {
        $largo = max(strlen($uno), strlen($otro));

        if ($largo === 0 || $uno === $otro) {
            return self::IGUALES;
        }

        return 1 - levenshtein($uno, $otro) / $largo;
    }

    private static function unoContieneAlOtro(string $uno, string $otro): bool
    {
        [$corto, $largo] = strlen($uno) <= strlen($otro) ? [$uno, $otro] : [$otro, $uno];
        $letras = strlen(str_replace(' ', '', $corto));

        return $letras >= self::LETRAS_MINIMAS_CONTENIDO && str_contains($largo, $corto);
    }

    private static function compartenLaMitadDeLasPalabras(string $uno, string $otro): bool
    {
        $palabrasUno = array_unique(explode(' ', $uno));
        $palabrasOtro = array_unique(explode(' ', $otro));
        $comunes = count(array_intersect($palabrasUno, $palabrasOtro));

        return $comunes > 0 && $comunes >= self::PROPORCION_PALABRAS * max(count($palabrasUno), count($palabrasOtro));
    }
}
