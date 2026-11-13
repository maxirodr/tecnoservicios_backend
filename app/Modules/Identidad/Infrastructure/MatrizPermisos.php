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
 * Lee la celda del sector del usuario y la recuerda solo mientras dura el pedido (TL-05): binding
 * *scoped* y `olvidar()` al terminar cada pedido y al guardar una celda o un sector
 * (IdentidadServiceProvider). Sin cache persistente: un cambio en la matriz vale desde el próximo
 * pedido al servidor (RN-IDE-14). Un usuario inactivo no puede nada (se mira siempre, no se recuerda).
 */
final class MatrizPermisos implements Permisos
{
    /** @var array<string, Permiso|null> celda por "sector|funcion" */
    private array $celdas = [];

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

    public function olvidar(): void
    {
        $this->celdas = [];
    }

    private function celda(User $user, string $funcion): ?Permiso
    {
        $clave = $user->sector_id.'|'.$funcion;

        if (! array_key_exists($clave, $this->celdas)) {
            $this->celdas[$clave] = Permiso::query()
                ->where('sector_id', $user->sector_id)
                ->whereHas('funcion', fn (Builder $query): Builder => $query->where('clave', $funcion))
                ->whereHas('sector', fn (Builder $query): Builder => $query->where('activo', true))
                ->first();
        }

        return $this->celdas[$clave];
    }
}
