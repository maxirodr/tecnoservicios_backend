<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Application\Obras\VersionDeObra;
use App\Modules\Obras\Application\Ports\MaterialesDePresupuesto;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * RN-OBR-24 (resuelta DU-A17): un adicional cargado por error se quita mientras no tenga montos
 * por rubro cargados ni, desde la semana 3, materiales del control por cantidad (puerto
 * `MaterialesDePresupuesto`); el principal no se quita. Se borran sus adjuntos y queda en el historial.
 */
final readonly class QuitarPresupuestoAction
{
    public const ACCION = 'presupuesto_quitado';

    public const CON_MATERIALES = 'Este presupuesto ya tiene materiales del control por cantidad: no se puede quitar.';

    public function __construct(
        private Connection $db,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
        private MaterialesDePresupuesto $materiales,
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
            VersionDeObra::bloquear($presupuesto->obra_id);

            if ($presupuesto->montos()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['presupuesto' => ['Este presupuesto ya tiene montos por rubro cargados: no se puede quitar.']]);
            }

            if ($this->materiales->tieneMateriales($presupuesto->id)) {
                throw ValidationException::withMessages(['presupuesto' => [self::CON_MATERIALES]]);
            }

            $this->adjuntos->quitar($presupuesto->adjuntos->modelKeys(), $presupuesto);
            $presupuesto->delete();
            $this->actividad->registrar($presupuesto->obra, self::ACCION, ['presupuesto' => ['antes' => $presupuesto->numero, 'despues' => null]]);
            VersionDeObra::subir($presupuesto->obra_id);
        });
    }
}
