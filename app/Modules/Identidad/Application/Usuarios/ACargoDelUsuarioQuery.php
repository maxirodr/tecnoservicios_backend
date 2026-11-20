<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Contracts\PedidosDePersonasYSectores;
use App\Modules\Identidad\Domain\Models\User;

/**
 * RN-IDE-24: lo que la ventana de W-39 avisa antes de desactivar a alguien ("tiene 2 personas a
 * cargo y es responsable de 3 obras"). No bloquea nada. Desde la semana 2 suma sus pedidos abiertos
 * ("Carla tiene 4 pedidos abiertos. Quedan como están hasta que los reasignes.", resuelta DU-P09) y, si es
 * la última persona activa de su sector, los pedidos que están en el sector sin persona ("Compras se
 * queda sin nadie y tiene N pedidos en el sector. Reasignalos.", resuelta DU-Q02).
 */
final class ACargoDelUsuarioQuery
{
    public function __construct(
        private readonly ObrasDelResponsable $obras,
        private readonly PedidosDePersonasYSectores $pedidos,
    ) {}

    /**
     * @return array{personas_a_cargo: int, obras_como_responsable: int, pedidos_abiertos: int, pedidos_en_el_sector: int}
     */
    public function handle(User $user): array
    {
        return [
            'personas_a_cargo' => $user->dependientes()->where('activo', true)->count(),
            'obras_como_responsable' => $this->obras->cantidad($user),
            'pedidos_abiertos' => $this->pedidos->abiertosDe($user->id),
            'pedidos_en_el_sector' => $this->esLaUltimaActivaDelSector($user) ? $this->pedidos->abiertosEnElSector($user->sector_id) : 0,
        ];
    }

    private function esLaUltimaActivaDelSector(User $user): bool
    {
        return ! User::query()
            ->where('sector_id', $user->sector_id)
            ->where('activo', true)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
