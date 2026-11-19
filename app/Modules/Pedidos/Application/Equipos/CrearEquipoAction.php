<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * "+ Agregar equipo" (W-49, HU-050 c2): obligatorios equipo y falla o trabajo (resuelta DU-P18); nace No
 * iniciada y Pendiente de aprobación salvo que se elija otro. Queda en `actividades`; no genera avisos.
 */
final readonly class CrearEquipoAction
{
    public const ACCION = 'equipo_agregado';

    public function __construct(
        private Connection $db,
        private AdjuntosDelEquipo $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, DatosEquipo $datos): EquipoReparacion
    {
        return $this->db->transaction(function () use ($actor, $datos): EquipoReparacion {
            $equipo = new EquipoReparacion(array_filter($datos->campos, static fn (?string $valor): bool => $valor !== null));
            $equipo->created_by = $actor->id;
            $equipo->updated_by = $actor->id;
            $equipo->save();
            $equipo->refresh();
            $this->adjuntos->aplicar($actor->id, $equipo, $datos->adjuntos);
            $this->actividad->registrar($equipo, self::ACCION, CamposDelEquipo::diferencias(CamposDelEquipo::valores(null), CamposDelEquipo::valores($equipo)));

            return $equipo;
        });
    }
}
