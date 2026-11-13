<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain;

use Illuminate\Support\Str;

/**
 * Forma comparable de un nombre: sin mayúsculas, sin tildes y sin espacios de más
 * (Cómpras con espacios = compras). Base del nombre único de sector (identidad.md §4.2).
 */
final class NombreClave
{
    public static function de(string $nombre): string
    {
        return Str::of($nombre)->ascii()->lower()->squish()->toString();
    }
}
