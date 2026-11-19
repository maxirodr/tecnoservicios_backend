<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Domain\Enums\Prioridad;

/** PedidoAlta ya validado en el borde (pedidos.md §11.1). Fechas "AAAA-MM-DD". */
final readonly class DatosPedido
{
    /**
     * @param  list<int>  $seguidores
     * @param  list<int>  $adjuntos
     */
    public function __construct(
        public string $tarea,
        public ?int $obraId,
        public int $sectorId,
        public int $propietarioId,
        public array $seguidores,
        public ?string $conQuien,
        public ?string $fechaPedido,
        public string $fechaEntrega,
        public ?string $periodoInicio,
        public ?string $periodoFin,
        public array $adjuntos,
        public ?string $aclaraciones,
        public ?Prioridad $prioridad,
    ) {}
}
