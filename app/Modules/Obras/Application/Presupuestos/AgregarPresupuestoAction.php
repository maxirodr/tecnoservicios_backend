<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * F-18 / HU-015: suma un presupuesto adicional a la obra (también Terminada, RN-OBR-25) con su
 * oferta técnica y OC opcionales, archivo o link (RN-OBR-20). Queda "sin cargar" para administración.
 */
final readonly class AgregarPresupuestoAction
{
    public const ACCION = 'presupuesto_agregado';

    public function __construct(
        private Connection $db,
        private NumeroUnico $numeros,
        private RegistrarPresupuesto $presupuestos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, Obra $obra, DatosPresupuesto $datos): Presupuesto
    {
        return $this->db->transaction(function () use ($actor, $obra, $datos): Presupuesto {
            $this->numeros->exigir($datos->numero, 'numero');
            $presupuesto = $this->presupuestos->handle($obra, $datos, false, $actor->id, '');
            $this->actividad->registrar($obra, self::ACCION, ['presupuesto' => ['antes' => null, 'despues' => $datos->numero]]);

            return $presupuesto;
        });
    }
}
