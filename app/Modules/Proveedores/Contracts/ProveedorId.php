<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Contracts;

/** Identificador de un proveedor para los demás módulos (proveedores.md §10.2). */
final readonly class ProveedorId
{
    public function __construct(public int $valor) {}
}
