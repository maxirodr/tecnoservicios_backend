<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Sectores;

use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-31 (resuelta DU-A07): no se saca un sector con usuarios activos ni (resuelta DU-Q02) con pedidos
 * abiertos que están en el sector sin persona. Sin historia se borra (con sus celdas); con historia
 * (usuarios inactivos o pedidos) se archiva: sale de los desplegables y de la matriz, y lo histórico
 * conserva su nombre.
 */
final class SacarSectorAction
{
    public const BORRADO = 'borrado';

    public const ARCHIVADO = 'archivado';

    public function __construct(
        private readonly RegistroActividad $actividad,
        private readonly PedidosDePersonasYSectores $pedidos,
    ) {}

    /**
     * @return self::BORRADO|self::ARCHIVADO
     */
    public function handle(Sector $sector): string
    {
        $activos = $sector->usuarios()->where('activo', true)->count();

        if ($activos > 0) {
            throw ValidationException::withMessages(['sector' => [$this->mensajeConUsuarios($sector, $activos)]]);
        }

        $enElSector = $this->pedidos->abiertosEnElSector($sector->id);

        if ($enElSector > 0) {
            throw ValidationException::withMessages(['sector' => [$this->mensajeConPedidos($sector, $enElSector)]]);
        }

        return DB::transaction(function () use ($sector): string {
            if ($sector->usuarios()->exists() || $this->pedidos->sectorTienePedidos($sector->id)) {
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

    private function mensajeConPedidos(Sector $sector, int $pedidos): string
    {
        $cuales = $pedidos === 1 ? 'su tarea que está' : "sus {$pedidos} tareas que están";

        return "Antes de sacar {$sector->nombre}, reasigná {$cuales} en el sector.";
    }
}
