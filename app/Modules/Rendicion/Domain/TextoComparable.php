<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain;

use Illuminate\Support\Str;

/**
 * Forma comparable de un nombre de material o de un concepto: sin distinguir mayúsculas, tildes ni espacios
 * dobles (rendicion.md §4.2 y RN-REN-34): "cemento  X 50 KG" = "Cemento x 50 kg". Conserva la ñ, como la
 * clave de los proveedores (PU-043).
 */
final class TextoComparable
{
    public static function de(string $texto): string
    {
        $partes = (array) preg_split('/ñ/iu', $texto);
        $sinTildes = implode('ñ', array_map(static fn ($parte): string => Str::ascii((string) $parte), $partes));

        return Str::squish(mb_strtolower($sinTildes));
    }
}
