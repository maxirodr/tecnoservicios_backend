<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/**
 * Obras de una persona como responsable: filtro "Responsable" (RN-OBR-06) y alcance "solo sus
 * obras" de la matriz (ADR-006).
 */
final readonly class PorResponsable implements FiltroObras
{
    public function __construct(private int $userId) {}

    public function aplicar(Builder $query): void
    {
        $query->where('obras.responsable_id', $this->userId);
    }
}
