<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Sectores;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-31 (resuelta DU-A07): no se saca un sector con usuarios activos. Sin historia se borra
 * (con sus celdas); con historia (usuarios inactivos; desde la semana 2 también pedidos) se
 * archiva: sale de los desplegables y de la matriz, y lo histórico conserva su nombre.
 */
final class SacarSectorAction
{
    public const BORRADO = 'borrado';

    public const ARCHIVADO = 'archivado';

    public function __construct(private readonly RegistroActividad $actividad) {}

    /**
     * @return self::BORRADO|self::ARCHIVADO
     */
    public function handle(Sector $sector): string
    {
        $activos = $sector->usuarios()->where('activo', true)->count();

        if ($activos > 0) {
            throw ValidationException::withMessages(['sector' => [$this->mensajeConUsuarios($sector, $activos)]]);
        }

        return DB::transaction(function () use ($sector): string {
            if ($sector->usuarios()->exists()) {
                $sector->activo = false;
                $sector->save();
                $this->actividad->registrar($sector, self::ARCHIVADO);

                return self::ARCHIVADO;
            }

            $this->actividad->registrar($sector, self::BORRADO);
            $sector->delete();

            return self::BORRADO;
        });
    }

    private function mensajeConUsuarios(Sector $sector, int $activos): string
    {
        $usuarios = $activos === 1 ? 'a su usuario' : "a sus {$activos} usuarios";

        return "Antes de sacar {$sector->nombre}, pasá {$usuarios} a otro sector.";
    }
}
