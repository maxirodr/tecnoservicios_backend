<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure;

use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Contracts\ProveedorId;
use App\Modules\Proveedores\Contracts\ResumenProveedor;
use App\Modules\Proveedores\Domain\Cuit;
use App\Modules\Proveedores\Domain\Models\Proveedor;

final class ProveedoresConsultaEloquent implements ProveedoresConsulta
{
    public function existe(ProveedorId $id): bool
    {
        return Proveedor::query()->whereKey($id->valor)->exists();
    }

    public function resumen(ProveedorId $id): ?ResumenProveedor
    {
        $proveedor = Proveedor::query()->with('rubroHabitual')->find($id->valor);

        if ($proveedor === null) {
            return null;
        }

        return new ResumenProveedor(
            $id,
            $proveedor->nombre,
            $proveedor->cuit === null ? null : Cuit::formatear($proveedor->cuit),
            $proveedor->rubroHabitual?->clave,
        );
    }

    public function generico(): ProveedorId
    {
        return new ProveedorId((int) Proveedor::query()->where('es_generico', true)->valueOrFail('id'));
    }

    public function esGenerico(ProveedorId $id): bool
    {
        return Proveedor::query()->whereKey($id->valor)->where('es_generico', true)->exists();
    }
}
