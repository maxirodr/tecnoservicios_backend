<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Montos;

use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Domain\Importe;
use Illuminate\Database\Connection;

/**
 * RN-OBR-36 / HU-018 criterio 8: monto del presupuesto (lo que se cobra, sin IVA). Solo con
 * `monto_obra = editar` (lo verifica el controller). El historial solo lo ve quien tiene `monto_obra`.
 */
final readonly class CargarMontoObraAction
{
    public const ACCION = 'monto_obra_cambiado';

    public function __construct(
        private Connection $db,
        private RegistroActividad $actividad,
    ) {}

    public function handle(Presupuesto $presupuesto, ?Importe $monto): Presupuesto
    {
        return $this->db->transaction(function () use ($presupuesto, $monto): Presupuesto {
            $antes = $presupuesto->monto_obra;
            $presupuesto->monto_obra = $monto?->valor;
            $presupuesto->save();

            if ($antes !== $presupuesto->monto_obra) {
                $this->actividad->registrar($presupuesto, self::ACCION, ['monto_obra' => ['antes' => $antes, 'despues' => $presupuesto->monto_obra]]);
            }

            return $presupuesto;
        });
    }
}
