<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Sectores;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * F-59 / RN-IDE-30 (resuelta DU-A06): el sector nuevo es una fila más de la tabla con
 * Pedidos = Editar·todas, Equipos en reparación = Editar·todas, Obras = Ver·todas y el resto Sin acceso.
 */
final class CrearSectorAction
{
    private const NIVELES_INICIALES = [
        ClaveFuncion::Pedidos->value => Nivel::Editar,
        ClaveFuncion::EquiposReparacion->value => Nivel::Editar,
        ClaveFuncion::Obras->value => Nivel::Ver,
    ];

    public function __construct(private readonly RegistroActividad $actividad) {}

    public function handle(string $nombre): Sector
    {
        return DB::transaction(function () use ($nombre): Sector {
            $sector = Sector::query()->create(['nombre' => $nombre, 'es_direccion' => false, 'activo' => true]);

            foreach (Funcion::query()->orderBy('orden')->get() as $funcion) {
                Permiso::query()->create([
                    'sector_id' => $sector->id,
                    'funcion_id' => $funcion->id,
                    'nivel' => self::NIVELES_INICIALES[$funcion->clave] ?? Nivel::SinAcceso,
                    'alcance' => Alcance::Todas,
                ]);
            }

            $this->actividad->registrar($sector, 'creado');

            return $sector;
        });
    }
}
