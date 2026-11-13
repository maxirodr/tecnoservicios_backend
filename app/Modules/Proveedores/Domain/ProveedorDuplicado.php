<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Domain;

use DomainException;

/**
 * Cada proveedor se carga una vez (RN-PRV-04) y el CUIT no se repite (RN-PRV-07). Lleva el proveedor
 * que ya existe para que el formulario muestre el link a su ficha.
 */
final class ProveedorDuplicado extends DomainException
{
    public const CAMPO_NOMBRE = 'nombre';

    public const CAMPO_CUIT = 'cuit';

    private function __construct(
        public readonly string $campo,
        string $mensaje,
        public readonly int $existenteId,
        public readonly string $existenteNombre,
    ) {
        parent::__construct($mensaje);
    }

    public static function porNombre(int $id, string $nombre): self
    {
        return new self(self::CAMPO_NOMBRE, self::frase("Ya existe {$nombre}"), $id, $nombre);
    }

    public static function porCuit(int $id, string $nombre): self
    {
        return new self(self::CAMPO_CUIT, self::frase("Ese CUIT ya es de {$nombre}"), $id, $nombre);
    }

    /** "Ya existe Hormigonera Sur S.R.L." sin doble punto. */
    private static function frase(string $texto): string
    {
        return str_ends_with($texto, '.') ? $texto : $texto.'.';
    }
}
