<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Estados;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use DateTimeInterface;

/**
 * Patrón State de la obra (arquitectura §3): valida la transición contra la tabla de `ObraEstado`
 * y aplica sus efectos. Pasar a Terminada guarda quién y cuándo (F-22); volver de Terminada los
 * limpia y la obra vuelve a las activas (RN-OBR-29). No guarda: lo hace el caso de uso.
 */
final class ObraStateMachine
{
    /**
     * @throws TransicionNoPermitida
     */
    public function transicionar(Obra $obra, ObraEstado $destino, int $userId, DateTimeInterface $ahora): void
    {
        $actual = $obra->estado;

        if (! $actual->puedePasarA($destino)) {
            throw TransicionNoPermitida::entre($actual, $destino);
        }

        $obra->estado = $destino;

        if ($destino === ObraEstado::Terminada) {
            $obra->terminada_at = $ahora;
            $obra->terminada_por = $userId;

            return;
        }

        $obra->terminada_at = null;
        $obra->terminada_por = null;
    }
}
