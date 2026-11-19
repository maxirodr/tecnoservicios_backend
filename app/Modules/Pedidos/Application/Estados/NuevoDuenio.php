<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Estados;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Pedidos\Asignables;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Validation\ValidationException;

/**
 * A quién pasa el pedido al reasignar o al reabrir con el propietario inactivo (W-31): una persona (el
 * sector pasa a ser el suyo) **o** un sector (queda sin persona, DU-16). Valida con `Asignables`.
 */
final readonly class NuevoDuenio
{
    public function __construct(private Asignables $asignables) {}

    /**
     * Deja el pedido con el nuevo dueño (sin guardar) y devuelve el cambio para el recorrido.
     *
     * @return array{antes: string, despues: string}
     *
     * @throws ValidationException
     */
    public function aplicar(Pedido $pedido, ?int $propietarioId, ?int $sectorId): array
    {
        if (($propietarioId === null) === ($sectorId === null)) {
            throw ValidationException::withMessages(['propietario_id' => ['Elegí una persona o un sector.']]);
        }

        $antes = self::nombre($pedido->loadMissing(['propietario', 'sector'])->propietario, $pedido->sector);

        if ($propietarioId !== null) {
            $persona = $this->asignables->persona($propietarioId);

            if ($pedido->propietario_id === $persona->id) {
                throw ValidationException::withMessages(['propietario_id' => ['Ya es el propietario.']]);
            }

            $pedido->propietario_id = $persona->id;
            $pedido->sector_id = $persona->sector_id;

            return ['antes' => $antes, 'despues' => $persona->nombre];
        }

        $sector = $this->asignables->sectorConGente((int) $sectorId);

        if ($pedido->enElSector() && $pedido->sector_id === $sector->id) {
            throw ValidationException::withMessages(['sector_id' => ['Ya está en ese sector.']]);
        }

        $pedido->propietario_id = null;
        $pedido->sector_id = $sector->id;

        return ['antes' => $antes, 'despues' => self::nombre(null, $sector)];
    }

    /** "Carla" o "Compras (sector)" (W-27). */
    public static function nombre(?User $propietario, Sector $sector): string
    {
        return $propietario->nombre ?? "{$sector->nombre} (sector)";
    }
}
