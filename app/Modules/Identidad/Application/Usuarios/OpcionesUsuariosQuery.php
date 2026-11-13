<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lista corta de usuarios activos para los desplegables (responsable, propietario, "depende de"):
 * un inactivo no aparece en los desplegables nuevos (identidad.md §7).
 */
final class OpcionesUsuariosQuery
{
    /**
     * @return Collection<int, User>
     */
    public function handle(?string $buscar): Collection
    {
        return User::query()
            ->with('sector')
            ->where('activo', true)
            ->when($buscar !== null && $buscar !== '', fn (Builder $query): Builder => $query->where('nombre', 'like', TextoLike::contiene((string) $buscar)))
            ->orderBy('nombre')
            ->get();
    }
}
