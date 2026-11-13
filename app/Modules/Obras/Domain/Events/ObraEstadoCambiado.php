<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Events;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/** La obra cambió de estado (obras.md §10.4): Rendiciones en curso (semana 3) la escucha. */
final readonly class ObraEstadoCambiado implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $obraId,
        public ObraEstado $de,
        public ObraEstado $a,
    ) {}
}
