<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure;

use App\Modules\Pedidos\Contracts\PedidoParaRecordar;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use DateTimeInterface;

/**
 * Los pedidos abiertos que vencen en una fecha, para el "vence mañana" de Avisos (avisos.md RN-AVI-16).
 * La fecha es de calendario (la arma Avisos en hora de Argentina); si la entrega cambió, sale la nueva.
 */
final class PedidosParaRecordarEloquent implements PedidosParaRecordar
{
    public function conEntregaEl(DateTimeInterface $fecha): array
    {
        $dia = $fecha->format('Y-m-d');

        return array_values(Pedido::query()
            ->with('obra')
            ->whereIn('estado', PedidoEstado::abiertos())
            ->where('fecha_entrega', $dia)
            ->orderBy('id')
            ->get()
            ->map(static fn (Pedido $p): PedidoParaRecordar => new PedidoParaRecordar(
                $p->id,
                $p->propietario_id,
                $p->sector_id,
                $p->tarea,
                $p->obra?->nombre_trabajo,
                $dia,
            ))
            ->all());
    }
}
