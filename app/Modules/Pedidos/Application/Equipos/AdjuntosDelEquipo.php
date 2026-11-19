<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Validation\ValidationException;

/**
 * El inventario y el presupuesto del taller: uno de cada uno, archivo o link (ADR-014). Reemplazar quita el
 * anterior (su archivo se borra) y asocia el nuevo, dentro de la transacción del caso de uso; el 422 de un
 * adjunto ajeno o de otro contexto sale con el nombre del campo.
 */
final readonly class AdjuntosDelEquipo
{
    public function __construct(private AdjuntosService $adjuntos) {}

    /**
     * @param  array<string, int|null>  $nuevos  `inventario` y/o `presupuesto`
     * @return bool si cambió alguno
     *
     * @throws ValidationException
     */
    public function aplicar(int $actorId, EquipoReparacion $equipo, array $nuevos): bool
    {
        $cambio = false;

        foreach ($nuevos as $campo => $nuevo) {
            $actual = $campo === DatosEquipo::INVENTARIO ? $equipo->inventario?->id : $equipo->presupuesto?->id;

            if ($actual === $nuevo) {
                continue;
            }

            if ($actual !== null) {
                $this->adjuntos->quitar([$actual], $equipo);
            }

            if ($nuevo !== null) {
                $this->asociar($actorId, $nuevo, $equipo, $campo);
            }

            $cambio = true;
        }

        if ($cambio) {
            $equipo->unsetRelation('inventario')->unsetRelation('presupuesto');
        }

        return $cambio;
    }

    /**
     * @throws ValidationException
     */
    private function asociar(int $actorId, int $id, EquipoReparacion $equipo, string $campo): void
    {
        $contexto = $campo === DatosEquipo::INVENTARIO ? EquipoReparacion::CONTEXTO_INVENTARIO : EquipoReparacion::CONTEXTO_PRESUPUESTO;

        try {
            $this->adjuntos->asociar($actorId, [$id], $equipo, $contexto);
        } catch (ValidationException) {
            throw ValidationException::withMessages([$campo => [AdjuntosService::MENSAJE_NO_UTILIZABLE]]);
        }
    }
}
