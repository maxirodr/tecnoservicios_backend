<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Domain\NombreProveedor;
use App\Modules\Proveedores\Domain\ProveedorDuplicado;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * RN-PRV-04 y RN-PRV-07: el nombre (normalizado suave) y el CUIT no se repiten. La base lo garantiza
 * además con índices únicos; si dos altas se cruzan, el caso de uso vuelve a preguntar acá para
 * responder con el mismo error.
 */
final class UnicidadDeProveedor
{
    /**
     * Corre `$guardar` solo si no hay duplicado; si la base rechaza por un alta cruzada, el mismo error.
     *
     * @param  array<string, mixed>  $datos
     * @param  Closure(): Proveedor  $guardar
     *
     * @throws ProveedorDuplicado
     */
    public function guardar(array $datos, ?int $excluirId, Closure $guardar): Proveedor
    {
        $duplicado = $this->duplicado($datos, $excluirId);
        if ($duplicado !== null) {
            throw $duplicado;
        }

        try {
            return $guardar();
        } catch (UniqueConstraintViolationException $violacion) {
            throw $this->duplicado($datos, $excluirId) ?? $violacion;
        }
    }

    /**
     * El proveedor que ya tiene ese nombre o ese CUIT (primero el nombre), o null.
     *
     * @param  array<string, mixed>  $datos  columnas a guardar (`nombre`, `cuit`…)
     */
    private function duplicado(array $datos, ?int $excluirId = null): ?ProveedorDuplicado
    {
        $nombre = $datos['nombre'] ?? null;
        if (is_string($nombre)) {
            $mismoNombre = $this->otros($excluirId)->where('nombre_clave', NombreProveedor::clave($nombre))->first();
            if ($mismoNombre !== null) {
                return ProveedorDuplicado::porNombre($mismoNombre->id, $mismoNombre->nombre);
            }
        }

        $cuit = $datos['cuit'] ?? null;
        if (is_string($cuit)) {
            $mismoCuit = $this->otros($excluirId)->where('cuit', $cuit)->first();
            if ($mismoCuit !== null) {
                return ProveedorDuplicado::porCuit($mismoCuit->id, $mismoCuit->nombre);
            }
        }

        return null;
    }

    /**
     * @return Builder<Proveedor>
     */
    private function otros(?int $excluirId): Builder
    {
        return Proveedor::query()
            ->select(['id', 'nombre'])
            ->when($excluirId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excluirId));
    }
}
