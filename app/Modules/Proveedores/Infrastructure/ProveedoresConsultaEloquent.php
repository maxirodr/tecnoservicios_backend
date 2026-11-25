<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure;

use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Contracts\ProveedorId;
use App\Modules\Proveedores\Contracts\ResumenProveedor;
use App\Modules\Proveedores\Domain\Cuit;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Infrastructure\Consultas\Alfabetico;
use App\Modules\Proveedores\Infrastructure\Consultas\PorNombre;
use App\Modules\Proveedores\Infrastructure\Consultas\ProveedoresQuery;

final class ProveedoresConsultaEloquent implements ProveedoresConsulta
{
    public function existe(ProveedorId $id): bool
    {
        return Proveedor::query()->whereKey($id->valor)->exists();
    }

    public function resumen(ProveedorId $id): ?ResumenProveedor
    {
        $proveedor = Proveedor::query()->with('rubroHabitual')->find($id->valor);

        return $proveedor === null ? null : self::aResumen($proveedor);
    }

    public function generico(): ProveedorId
    {
        return new ProveedorId((int) Proveedor::query()->where('es_generico', true)->valueOrFail('id'));
    }

    public function esGenerico(ProveedorId $id): bool
    {
        return Proveedor::query()->whereKey($id->valor)->where('es_generico', true)->exists();
    }

    public function opcionesEntre(array $ids, ?string $buscar, int $limite): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values(ProveedoresQuery::con(new PorNombre($buscar), new Alfabetico)
            ->whereKey($ids)
            ->limit($limite)
            ->get()
            ->map(self::aResumen(...))
            ->all());
    }

    private static function aResumen(Proveedor $proveedor): ResumenProveedor
    {
        return new ResumenProveedor(
            new ProveedorId($proveedor->id),
            $proveedor->nombre,
            $proveedor->cuit === null ? null : Cuit::formatear($proveedor->cuit),
            $proveedor->rubroHabitual?->clave,
            $proveedor->es_generico,
        );
    }
}
