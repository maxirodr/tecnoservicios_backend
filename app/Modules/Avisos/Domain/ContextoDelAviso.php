<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/**
 * La foto del momento con la que se redacta un aviso (RN-AVI-07): nombres y datos del pedido tal como
 * estaban cuando pasó. `extracto` es el comentario, motivo o comentario de cierre, entero (se recorta al redactar).
 */
final readonly class ContextoDelAviso
{
    public function __construct(
        public string $tarea,
        public ?string $obraNombre,
        public ?string $actorNombre = null,
        public ?string $extracto = null,
        public int $comprobantes = 0,
        public ?string $solicitanteNombre = null,
        public ?string $sectorNombre = null,
    ) {}
}
