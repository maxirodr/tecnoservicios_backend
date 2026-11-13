<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Application\Filtros\Alfabetico;
use App\Modules\Proveedores\Application\Filtros\PorNombre;
use App\Modules\Proveedores\Application\ProveedoresQuery;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Shared\Busqueda\FuenteBusqueda;
use App\Shared\Busqueda\ResultadoBusqueda;

/**
 * Grupo "Proveedores" del buscador general (F-03, HU-022 crit. 3): por nombre, igual que el buscador
 * de W-14 (RN-PRV-03), solo para quien tiene Proveedores ≥ ver.
 */
final readonly class ProveedoresEnBuscador implements FuenteBusqueda
{
    public const TIPO = 'proveedores';

    public function __construct(private Permisos $permisos) {}

    public function puedeBuscar(User $user): bool
    {
        return $this->permisos->puede($user, ClaveFuncion::Proveedores->value, Nivel::Ver);
    }

    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda
    {
        $cantidad = ProveedoresQuery::con(new PorNombre($texto))->count();
        $primeros = ProveedoresQuery::con(new PorNombre($texto), new Alfabetico)->limit($limite)->get();

        return new ResultadoBusqueda($cantidad, array_values($primeros->map(static fn (Proveedor $proveedor): array => [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'rubro_habitual' => $proveedor->rubroHabitual === null
                ? null
                : ['clave' => $proveedor->rubroHabitual->clave, 'nombre' => $proveedor->rubroHabitual->nombre],
            'es_generico' => $proveedor->es_generico,
        ])->all()));
    }
}
