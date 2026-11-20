<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

/**
 * Puerto que implementa el módulo Pedidos (HU-058): lo que Identidad avisa o frena al desactivar a una
 * persona (W-39) o sacar un sector (W-40) que tienen pedidos (resueltas DU-P09 y DU-Q02). Mientras Pedidos
 * no registre su implementación, Identidad usa una que dice que no hay pedidos.
 */
interface PedidosDePersonasYSectores
{
    /** Pedidos abiertos de los que la persona es propietaria ("Carla tiene 4 pedidos abiertos"). */
    public function abiertosDe(int $userId): int;

    /** Pedidos abiertos que están en el sector, sin persona (resuelta DU-16). */
    public function abiertosEnElSector(int $sectorId): int;

    /** Si el sector tiene historia en pedidos (abiertos o completados): entonces se archiva, no se borra. */
    public function sectorTienePedidos(int $sectorId): bool;
}
