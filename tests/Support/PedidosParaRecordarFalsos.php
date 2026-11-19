<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Pedidos\Contracts\PedidoParaRecordar;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use DateTimeInterface;

/** Fake del contrato de Pedidos para el recordatorio: los abiertos cargados a mano, filtrados por la fecha pedida. */
final class PedidosParaRecordarFalsos implements PedidosParaRecordar
{
    /** @var list<PedidoParaRecordar> */
    public array $abiertos = [];

    public function conEntregaEl(DateTimeInterface $fecha): array
    {
        return array_values(array_filter($this->abiertos, static fn (PedidoParaRecordar $p): bool => $p->fechaEntrega === $fecha->format('Y-m-d')));
    }
}
