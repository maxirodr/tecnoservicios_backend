<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Montos;

use App\Modules\Obras\Application\Obras\VersionDeObra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Concurrencia\VersionDeFila;
use App\Shared\Domain\Importe;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * RN-OBR-36 / HU-018 criterio 8: monto del presupuesto (lo que se cobra, sin IVA). Solo con
 * `monto_obra = editar` (lo verifica el controller). El historial solo lo ve quien tiene `monto_obra`.
 * Con la versión del presupuesto que leyó la pantalla (`updated_at`, PU-201): si cambió, 409.
 */
final readonly class CargarMontoObraAction
{
    public const ACCION = 'monto_obra_cambiado';

    public function __construct(
        private Connection $db,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws HttpResponseException 409 si el presupuesto cambió desde `$version` (PU-201)
     */
    public function handle(Presupuesto $presupuesto, ?Importe $monto, ?CarbonInterface $version = null): Presupuesto
    {
        return $this->db->transaction(function () use ($presupuesto, $monto, $version): Presupuesto {
            VersionDeObra::bloquear($presupuesto->obra_id);
            VersionDeFila::bloquearYVerificar($presupuesto, $version);
            $antes = $presupuesto->monto_obra;
            $presupuesto->monto_obra = $monto?->valor;
            $presupuesto->save();

            if ($antes !== $presupuesto->monto_obra) {
                $this->actividad->registrar($presupuesto, self::ACCION, ['monto_obra' => ['antes' => $antes, 'despues' => $presupuesto->monto_obra]]);
                VersionDeObra::subir($presupuesto->obra_id);
            }

            return $presupuesto;
        });
    }
}
