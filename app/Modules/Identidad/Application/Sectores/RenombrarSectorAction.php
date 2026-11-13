<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Sectores;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * RN-IDE-32: renombrar no cambia nada más (usuarios, permisos y pedidos siguen en el mismo sector).
 */
final class RenombrarSectorAction
{
    public function __construct(private readonly RegistroActividad $actividad) {}

    public function handle(Sector $sector, string $nombre): Sector
    {
        $antes = $sector->nombre;

        if ($antes === $nombre) {
            return $sector;
        }

        return DB::transaction(function () use ($sector, $nombre, $antes): Sector {
            $sector->nombre = $nombre;
            $sector->save();

            $this->actividad->registrar($sector, 'editado', ['nombre' => ['antes' => $antes, 'despues' => $nombre]]);

            return $sector;
        });
    }
}
