<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Contracts;

use DateTimeInterface;

/** Pedidos lo implementa; Avisos lo usa para el recordatorio "vence mañana" (pedidos.md §11.4). */
interface PedidosParaRecordar
{
    /**
     * Solo pedidos abiertos (no_iniciada, en_curso, bloqueada) con esa fecha de entrega.
     *
     * @return list<PedidoParaRecordar>
     */
    public function conEntregaEl(DateTimeInterface $fecha): array;
}
