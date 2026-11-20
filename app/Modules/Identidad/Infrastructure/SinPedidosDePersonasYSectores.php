<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;

/** Implementación por defecto hasta que el módulo Pedidos registre la suya. */
final class SinPedidosDePersonasYSectores implements PedidosDePersonasYSectores
{
    public function abiertosDe(int $userId): int
    {
        return 0;
    }

    public function abiertosEnElSector(int $sectorId): int
    {
        return 0;
    }

    public function sectorTienePedidos(int $sectorId): bool
    {
        return false;
    }
}
