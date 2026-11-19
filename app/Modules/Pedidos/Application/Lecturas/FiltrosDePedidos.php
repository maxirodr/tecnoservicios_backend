<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;

/**
 * Filtros y orden de Todos los pedidos, del exportar y de la pestaña Pedidos de la obra (RN-PED-43, 44):
 * se suman entre sí (Y). `obra` = id o `sin_obra`; `propietario` = id o `en_sector`.
 */
final readonly class FiltrosDePedidos
{
    public function __construct(
        public int|string|null $obra = null,
        public ?int $sectorId = null,
        public int|string|null $propietario = null,
        public ?int $solicitanteId = null,
        public string $estado = PedidosQuery::ABIERTOS,
        public ?Prioridad $prioridad = null,
        public bool $soloVencidos = false,
        public ?string $entregaDesde = null,
        public ?string $entregaHasta = null,
        public string $orden = PedidosQuery::ORDEN_ENTREGA,
        public string $direccion = PedidosQuery::ASC,
    ) {}
}
