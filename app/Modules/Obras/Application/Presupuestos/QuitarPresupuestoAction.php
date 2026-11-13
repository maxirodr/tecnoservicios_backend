<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * RN-OBR-24 (resuelta DU-A17): un adicional cargado por error se quita mientras no tenga montos
 * por rubro cargados; el principal no se quita. Se borran sus adjuntos y queda en el historial.
 */
final readonly class QuitarPresupuestoAction
{
    public const ACCION = 'presupuesto_quitado';

    public function __construct(
        private Connection $db,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Presupuesto $presupuesto): void
    {
        if ($presupuesto->es_principal) {
            throw ValidationException::withMessages(['presupuesto' => ['El presupuesto principal no se puede quitar: corregilo.']]);
        }

        $this->db->transaction(function () use ($presupuesto): void {
            if ($presupuesto->montos()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['presupuesto' => ['Este presupuesto ya tiene montos por rubro cargados: no se puede quitar.']]);
            }

            $this->adjuntos->quitar($presupuesto->adjuntos->modelKeys(), $presupuesto);
            $presupuesto->delete();
            $this->actividad->registrar($presupuesto->obra, self::ACCION, ['presupuesto' => ['antes' => $presupuesto->numero, 'despues' => null]]);
        });
    }
}
