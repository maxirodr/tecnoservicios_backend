<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Domain\Models\User;

/**
 * "Mi gente" (RN-PED-35): quienes dependen de mí, directa o indirectamente; para alguien de un sector
 * con `es_direccion`, todos. Se recuerda solo mientras dura el pedido al servidor (binding scoped).
 */
final class MiGente
{
    /** @var array<int, list<int>> */
    private array $subordinados = [];

    public function __construct(private readonly Jerarquia $jerarquia) {}

    public function esDireccion(User $user): bool
    {
        return (bool) $user->loadMissing('sector')->sector->es_direccion;
    }

    public function incluye(User $user, int $otroId): bool
    {
        return $this->esDireccion($user) || in_array($otroId, $this->de($user), true);
    }

    /** "Tareas de mi gente" aparece con al menos un usuario activo a cargo o en Dirección (RN-PED-35). */
    public function tieneGenteActiva(User $user): bool
    {
        if ($this->esDireccion($user)) {
            return true;
        }

        $ids = $this->de($user);

        return $ids !== [] && User::query()->whereKey($ids)->where('activo', true)->exists();
    }

    /**
     * Ids de mi gente; `null` = todos (Dirección).
     *
     * @return list<int>|null
     */
    public function ids(User $user): ?array
    {
        return $this->esDireccion($user) ? null : $this->de($user);
    }

    /**
     * @return list<int>
     */
    private function de(User $user): array
    {
        return $this->subordinados[$user->id] ??= $this->jerarquia->subordinados($user);
    }
}
