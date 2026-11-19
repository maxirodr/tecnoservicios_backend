<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Pedidos\Domain\Models\Pedido;

/** Resultado de crear o reasignar: el pedido y a quiénes se les avisó (RN-PED-08, §11.1 `avisados`). */
final readonly class PedidoConAvisados
{
    /**
     * @param  list<array{id: int, nombre: string}>  $avisados
     */
    public function __construct(
        public Pedido $pedido,
        public array $avisados,
    ) {}
}
