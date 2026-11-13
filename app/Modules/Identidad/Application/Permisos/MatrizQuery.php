<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Permisos;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use Illuminate\Database\Eloquent\Builder;

/**
 * W-15 tabla de permisos (F-60): funciones del catálogo, sectores activos y sus celdas.
 * Los sectores archivados salen de la tabla (RN-IDE-31).
 */
final class MatrizQuery
{
    /**
     * @return array{
     *     funciones: list<array{clave: string, nombre: string, admite_editar: bool, admite_alcance: bool, orden: int}>,
     *     sectores: list<array{id: int, nombre: string}>,
     *     celdas: list<array{sector_id: int, funcion: string, nivel: string, alcance: string}>
     * }
     */
    public function handle(): array
    {
        $funciones = Funcion::query()->orderBy('orden')->get();
        $sectores = Sector::query()->where('activo', true)->orderBy('id')->get();
        $claves = $funciones->pluck('clave', 'id');

        $celdas = Permiso::query()
            ->whereHas('sector', fn (Builder $query): Builder => $query->where('activo', true))
            ->orderBy('sector_id')
            ->orderBy('funcion_id')
            ->get();

        return [
            'funciones' => array_values($funciones->map(fn (Funcion $funcion): array => [
                'clave' => $funcion->clave,
                'nombre' => $funcion->nombre,
                'admite_editar' => $funcion->admite_editar,
                'admite_alcance' => $funcion->admite_alcance,
                'orden' => $funcion->orden,
            ])->all()),
            'sectores' => array_values($sectores->map(fn (Sector $sector): array => [
                'id' => $sector->id,
                'nombre' => $sector->nombre,
            ])->all()),
            'celdas' => array_values($celdas->map(fn (Permiso $celda): array => [
                'sector_id' => $celda->sector_id,
                'funcion' => (string) $claves->get($celda->funcion_id),
                'nivel' => $celda->nivel->value,
                'alcance' => $celda->alcance->value,
            ])->all()),
        ];
    }
}
