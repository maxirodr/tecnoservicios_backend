<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/** Cambió lo presupuestado de la obra (F-48): invalida el cache de su rendición (ADR-007). */
final readonly class PresupuestoMontosCambiados implements ShouldDispatchAfterCommit
{
    public function __construct(public int $obraId) {}
}
