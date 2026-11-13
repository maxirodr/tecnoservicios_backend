<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lee la celda del sector del usuario en cada llamada (sin cache): un cambio en la matriz vale
 * desde el próximo pedido al servidor (RN-IDE-14). Un usuario inactivo no puede nada.
 */
final class MatrizPermisos implements Permisos
{
    public function puede(User $user, string $funcion, Nivel $nivelMinimo): bool
    {
        if (! $user->activo) {
            return false;
        }

        $nivel = $this->celda($user, $funcion)->nivel ?? Nivel::SinAcceso;

        return $nivel->alcanza($nivelMinimo);
    }

    public function alcance(User $user, string $funcion): Alcance
    {
        return $this->celda($user, $funcion)->alcance ?? Alcance::Todas;
    }

    private function celda(User $user, string $funcion): ?Permiso
    {
        return Permiso::query()
            ->where('sector_id', $user->sector_id)
            ->whereHas('funcion', fn (Builder $query): Builder => $query->where('clave', $funcion))
            ->whereHas('sector', fn (Builder $query): Builder => $query->where('activo', true))
            ->first();
    }
}
