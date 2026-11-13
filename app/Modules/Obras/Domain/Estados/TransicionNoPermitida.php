<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Estados;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use DomainException;

/** Cambio de estado que la tabla de transiciones no admite (RN-OBR-29, obras.md §9 casos 9 y 21). */
final class TransicionNoPermitida extends DomainException
{
    public static function entre(ObraEstado $actual, ObraEstado $destino): self
    {
        if ($actual === $destino) {
            return new self("La obra ya está {$actual->etiqueta()}.");
        }

        return new self(match ($actual) {
            ObraEstado::Confirmada => 'De Confirmada se pasa primero a En ejecución.',
            ObraEstado::Terminada => 'De Terminada se vuelve primero a En ejecución.',
            ObraEstado::EnEjecucion => "No se puede pasar de {$actual->etiqueta()} a {$destino->etiqueta()}.",
        });
    }
}
