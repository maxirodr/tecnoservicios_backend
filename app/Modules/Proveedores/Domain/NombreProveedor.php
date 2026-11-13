<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Domain;

use Illuminate\Support\Str;

/**
 * Formas comparables del nombre de un proveedor (proveedores.md §4.1):
 * - `busqueda`: minúsculas, sin tildes, sin puntuación y espacios simples ("Corralón Centro S.R.L." →
 *   "corralon centro srl"). La recorre el buscador y ordena la lista (RN-PRV-02/03).
 * - `clave` (suave): además sin forma societaria al final ("hormigonera sur"). Única (RN-PRV-04).
 * - `normalizado` (fuerte): además sin artículos ni "y" ("Hormigonera del Sur" → "hormigonera sur").
 *   Base del aviso de parecidos (RN-PRV-08).
 */
final class NombreProveedor
{
    /** Formas societarias, ya sin puntuación; "y cia" va antes que "cia". */
    private const FORMAS_SOCIETARIAS = ['y cia', 'sacif', 'saic', 'sac', 'sas', 'sca', 'srl', 'sa', 'sh', 'cia'];

    private const PALABRAS_VACIAS = ['de', 'del', 'la', 'las', 'el', 'los', 'y'];

    public static function busqueda(string $nombre): string
    {
        $texto = Str::of($nombre)->ascii()->lower()->toString();
        // Puntos, comas y apóstrofos pegan ("S.R.L." → "srl"); el resto de los signos separa palabras.
        $texto = (string) preg_replace("/[.,'`]/", '', $texto);
        $texto = (string) preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return self::unirIniciales(Str::squish($texto));
    }

    public static function clave(string $nombre): string
    {
        $busqueda = self::busqueda($nombre);
        $clave = $busqueda;

        // "Pérez y Cía. S.A.": se saca de a una forma desde el final, volviendo a mirar la lista entera.
        while (($forma = self::formaAlFinal($clave)) !== null) {
            $clave = substr($clave, 0, -strlen(' '.$forma));
        }

        return $clave;
    }

    public static function normalizado(string $nombre): string
    {
        $clave = self::clave($nombre);
        $palabras = array_filter(
            explode(' ', $clave),
            static fn (string $palabra): bool => ! in_array($palabra, self::PALABRAS_VACIAS, true),
        );

        return $palabras === [] ? $clave : implode(' ', $palabras);
    }

    private static function formaAlFinal(string $texto): ?string
    {
        foreach (self::FORMAS_SOCIETARIAS as $forma) {
            if (str_ends_with($texto, ' '.$forma)) {
                return $forma;
            }
        }

        return null;
    }

    /** "s a" (de "S. A.") o "s r l" se leen como una sola palabra: "sa", "srl". */
    private static function unirIniciales(string $texto): string
    {
        return (string) preg_replace_callback(
            '/\b[a-z](?: [a-z]\b)+/',
            static fn (array $iniciales): string => str_replace(' ', '', $iniciales[0]),
            $texto,
        );
    }
}
