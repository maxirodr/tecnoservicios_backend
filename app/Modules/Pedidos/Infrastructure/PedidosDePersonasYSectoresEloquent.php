<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure;

use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * Pedidos implementa el puerto de Identidad (HU-058): los abiertos de una persona (W-39, resuelta DU-P09),
 * los que están en un sector sin persona (W-39 y W-40, resuelta DU-Q02) y si el sector tiene pedidos
 * (entonces se archiva). Cuenta todos: es gerencia decidiendo sobre la persona o el sector, no un listado.
 */
final class PedidosDePersonasYSectoresEloquent implements PedidosDePersonasYSectores
{
    public function abiertosDe(int $userId): int
    {
        return Pedido::query()->where('propietario_id', $userId)->whereIn('estado', PedidoEstado::abiertos())->count();
    }

    public function abiertosEnElSector(int $sectorId): int
    {
        return Pedido::query()->where('sector_id', $sectorId)->whereNull('propietario_id')->whereIn('estado', PedidoEstado::abiertos())->count();
    }

    public function sectorTienePedidos(int $sectorId): bool
    {
        return Pedido::query()->where('sector_id', $sectorId)->exists();
    }
}
