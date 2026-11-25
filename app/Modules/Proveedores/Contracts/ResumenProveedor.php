<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Contracts;

/**
 * Lo que "trae sus datos" al elegir el proveedor en la factura (F-42): CUIT formateado
 * (`30-00000001-5`), la clave del rubro habitual (sugiere el rubro del renglón) y si es el genérico
 * "Gastos sin comprobante" (RN-PRV-12).
 */
final readonly class ResumenProveedor
{
    public function __construct(
        public ProveedorId $id,
        public string $nombre,
        public ?string $cuit,
        public ?string $rubroHabitual,
        public bool $esGenerico = false,
    ) {}
}
