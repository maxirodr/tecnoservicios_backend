<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Cambió la lista de materiales de W-48 (rendicion.md §8). Lo que pasó ya queda en `actividades` dentro de la
 * transacción; W-48 no se cachea, así que nadie más necesita escucharlo en la Etapa 1.
 */
final readonly class MaterialCambiado implements ShouldDispatchAfterCommit
{
    public const AGREGADO = 'agregado';

    public const CORREGIDO = 'corregido';

    public const QUITADO = 'quitado';

    public function __construct(
        public int $materialId,
        public int $obraId,
        public string $accion,
    ) {}
}
