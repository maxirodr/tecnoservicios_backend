<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * Editar un equipo (W-49): estado y aprobación se cambian libremente y uno no mueve al otro (RN-PED-57,
 * el sistema no decide). Cada cambio queda con quién, cuándo y antes → después; sin cambios no registra.
 */
final readonly class ActualizarEquipoAction
{
    public const ACCION = 'equipo_editado';

    public function __construct(
        private Connection $db,
        private AdjuntosDelEquipo $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 409 si cambió mientras lo tenían abierto
     */
    public function handle(User $actor, EquipoReparacion $equipo, DatosEquipo $datos, ?CarbonInterface $version): EquipoReparacion
    {
        return $this->db->transaction(function () use ($actor, $equipo, $datos, $version): EquipoReparacion {
            VersionDeFila::bloquearYVerificar($equipo, $version);
            $antes = CamposDelEquipo::valores($equipo);
            $equipo->fill($datos->campos);
            $cambios = CamposDelEquipo::diferencias($antes, CamposDelEquipo::valores($equipo));
            $cambiaronAdjuntos = $this->adjuntos->aplicar($actor->id, $equipo, $datos->adjuntos);

            if ($cambios !== [] || $cambiaronAdjuntos) {
                $equipo->updated_by = $actor->id;
                $equipo->touch();
            }

            if ($cambios !== []) {
                $this->actividad->registrar($equipo, self::ACCION, $cambios);
            }

            return $equipo;
        });
    }
}
