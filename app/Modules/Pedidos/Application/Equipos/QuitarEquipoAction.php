<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Pedidos\Domain\Enums\EquipoEstado;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * Un equipo cargado por error se quita mientras siga No iniciada; después se corrige (resuelta DU-P18). Se
 * quitan sus adjuntos (y sus archivos) y queda en `actividades` con lo que tenía.
 */
final readonly class QuitarEquipoAction
{
    public const ACCION = 'equipo_quitado';

    public function __construct(
        private Connection $db,
        private AdjuntosService $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException 422 si ya no está No iniciada
     */
    public function handle(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $equipo = EquipoReparacion::query()->with(['inventario', 'presupuesto'])->lockForUpdate()->findOrFail($id);

            if ($equipo->estado !== EquipoEstado::NoIniciada) {
                throw ValidationException::withMessages(['estado' => [
                    'Ya está '.mb_strtolower($equipo->estado->etiqueta()).': corregilo en lugar de quitarlo.',
                ]]);
            }

            $this->adjuntos->quitar(array_values(array_filter([$equipo->inventario?->id, $equipo->presupuesto?->id])), $equipo);
            $this->actividad->registrar($equipo, self::ACCION, CamposDelEquipo::diferencias(CamposDelEquipo::valores($equipo), CamposDelEquipo::valores(null)));
            $equipo->delete();
        });
    }
}
