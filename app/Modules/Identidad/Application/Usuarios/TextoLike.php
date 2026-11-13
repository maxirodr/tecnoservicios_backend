<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

/**
 * Patrón de LIKE "contiene" con los comodines del usuario escapados (TL-09): buscar "50%" o "a_b"
 * busca ese texto literal, no "cualquier cosa". Igual que Obras y Proveedores.
 */
final class TextoLike
{
    private const COMODINES = '\\%_';

    public static function contiene(string $texto): string
    {
        return '%'.addcslashes($texto, self::COMODINES).'%';
    }
}
