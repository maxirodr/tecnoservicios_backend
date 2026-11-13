<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Database\Eloquent\Builder;

/** Pestaña del listado (RN-OBR-01). */
final readonly class PorEstado implements FiltroObras
{
    public function __construct(private ObraEstado $estado) {}

    public function aplicar(Builder $query): void
    {
        $query->where('obras.estado', $this->estado->value);
    }
}
