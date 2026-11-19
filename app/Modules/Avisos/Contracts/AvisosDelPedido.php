<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Contracts;

/** Avisos lo implementa; Pedidos lo usa en el recorrido del pedido (pedidos.md RN-PED-31). */
interface AvisosDelPedido
{
    /**
     * @return list<AvisoEnviado> agrupados por evento, del más viejo al más nuevo
     */
    public function enviadosDe(int $pedidoId): array;
}
