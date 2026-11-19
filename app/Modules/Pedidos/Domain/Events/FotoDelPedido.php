<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Events;

/**
 * El pedido como estaba al momento del evento: Avisos arma el texto y el mail con esto ("foto del
 * momento", avisos.md RN-AVI-07 y RN-AVI-21), sin volver a consultar Pedidos.
 */
final readonly class FotoDelPedido
{
    public function __construct(
        public string $tarea,
        public ?int $obraId,
        /** `nombre_trabajo` de la obra; null = pedido sin obra. */
        public ?string $obraNombre,
        /** "2026-11-20" (calendario, hora de Argentina); null solo en carga inicial. */
        public ?string $fechaEntrega,
        /** `urgente` | `prioritario` | `normal`. */
        public string $prioridad,
        /** `no_iniciada` | `en_curso` | `bloqueada` | `completada`. */
        public string $estado,
    ) {}
}
