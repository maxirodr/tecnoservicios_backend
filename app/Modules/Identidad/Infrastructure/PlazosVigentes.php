<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Identidad\Domain\Models\Plazos;
use DateTimeInterface;

/**
 * Calcula con los plazos guardados al momento de la llamada (RN-IDE-42/43: no recalcula lo cargado).
 */
final class PlazosVigentes implements PlazosPrioridad
{
    public function calcular(DateTimeInterface $fechaPedido, DateTimeInterface $fechaEntrega): Prioridad
    {
        return Plazos::query()->firstOrFail()->calculador()->calcular($fechaPedido, $fechaEntrega);
    }
}
