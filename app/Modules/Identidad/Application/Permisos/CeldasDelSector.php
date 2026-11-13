<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Permisos;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;

/**
 * Las 12 celdas del sector, en el orden del catálogo. Una celda que falte (o un sector archivado)
 * cuenta como sin acceso (F-02, `GET /api/me`: "permisos trae las 12 claves siempre").
 */
final class CeldasDelSector
{
    /**
     * @return array<string, array{nivel: Nivel, alcance: Alcance}>
     */
    public function handle(int $sectorId): array
    {
        $celdas = Permiso::query()
            ->where('sector_id', $sectorId)
            ->whereHas('sector', fn ($query) => $query->where('activo', true))
            ->get()
            ->keyBy('funcion_id');

        $resultado = [];

        foreach (Funcion::query()->orderBy('orden')->get() as $funcion) {
            $celda = $celdas->get($funcion->id);
            $resultado[$funcion->clave] = [
                'nivel' => $celda instanceof Permiso ? $celda->nivel : Nivel::SinAcceso,
                'alcance' => $celda instanceof Permiso ? $celda->alcance : Alcance::Todas,
            ];
        }

        return $resultado;
    }
}
