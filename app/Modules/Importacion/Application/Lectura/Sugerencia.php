<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Lectura;

use App\Modules\Importacion\Domain\Lectura\Valores;

/**
 * El "sugerido" de Rubros por revisar y Personas por revisar (§5.8): solo una ayuda para administración, **nunca se
 * aplica** (DU-21). El más parecido sin mayúsculas ni tildes, si se parece lo suficiente; si no, "—".
 */
final class Sugerencia
{
    public const NINGUNA = '—';

    private const PARECIDO_MINIMO = 2;

    private const PROPORCION = 3;

    /**
     * @param  list<string>  $opciones
     */
    public static function para(string $escrito, array $opciones): string
    {
        $buscado = Valores::clave($escrito);
        $mejor = self::NINGUNA;
        $distancia = PHP_INT_MAX;

        foreach ($opciones as $opcion) {
            $clave = Valores::clave($opcion);
            $primera = explode(' ', $clave)[0];
            $d = str_starts_with($clave, $buscado) || str_starts_with($buscado, $clave)
                ? 0
                : min(levenshtein($buscado, $clave), levenshtein($buscado, $primera));

            if ($d < $distancia) {
                [$mejor, $distancia] = [$opcion, $d];
            }
        }

        return $distancia <= max(self::PARECIDO_MINIMO, intdiv(mb_strlen($buscado), self::PROPORCION)) ? $mejor : self::NINGUNA;
    }
}
