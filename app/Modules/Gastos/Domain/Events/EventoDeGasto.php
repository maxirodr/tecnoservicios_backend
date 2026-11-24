<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Base de los eventos de Gastos (rendicion.md §8). Se despachan **después del commit**. `obraIds` son
 * **todas** las obras afectadas (en una corrección, las de antes y las de después), sin repetir. Gastos
 * los dispara; Rendicion los escucha para invalidar su cache.
 */
abstract readonly class EventoDeGasto implements ShouldDispatchAfterCommit
{
    /**
     * @param  list<int>  $obraIds
     */
    public function __construct(
        public int $gastoId,
        public array $obraIds,
    ) {}
}
