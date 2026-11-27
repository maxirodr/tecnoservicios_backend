<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Contracts;

/**
 * Una fila "Tarea" de la minuta, ya leída (`importacion.md` §5.6). Fechas "AAAA-MM-DD". `propietarioId` null =
 * "(en el sector)" con `sectorId`; `prioridad` null = vacía o P3 en la minuta (RN-IMP-35); `notas` va como motivo
 * de bloqueo si está Bloqueada y como aclaraciones si no (RN-IMP-34).
 */
final readonly class TareaInicial
{
    /**
     * @param  string  $estado  un valor abierto de `PedidoEstado` (no_iniciada · en_curso · bloqueada)
     * @param  string|null  $prioridad  un valor de `Prioridad` (urgente · prioritario · normal)
     * @param  list<string>  $links
     */
    public function __construct(
        public string $tarea,
        public ?int $obraId,
        public ?int $propietarioId,
        public ?int $sectorId,
        public ?int $solicitanteId,
        public string $estado,
        public ?string $conQuien,
        public ?string $fechaPedido,
        public string $fechaEntrega,
        public ?string $periodoInicio,
        public ?string $periodoFin,
        public ?string $prioridad,
        public ?string $notas,
        public array $links,
    ) {}
}
