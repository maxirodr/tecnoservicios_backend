<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Contracts;

/**
 * Lo que Proveedores expone a los demás módulos (proveedores.md §10.2). Gastos (semana 3) elige el
 * proveedor de la factura y valida que el genérico solo vaya con "sin documento" (RN-PRV-14).
 */
interface ProveedoresConsulta
{
    public function existe(ProveedorId $id): bool;

    /** null si no existe. */
    public function resumen(ProveedorId $id): ?ResumenProveedor;

    /** El proveedor "Gastos sin comprobante" (RN-PRV-12), creado por migración. */
    public function generico(): ProveedorId;

    public function esGenerico(ProveedorId $id): bool;
}
