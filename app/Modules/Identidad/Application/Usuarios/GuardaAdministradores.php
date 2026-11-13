<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-25 (resuelta DU-A09): ningún cambio (baja, cambio de sector o de matriz) puede dejar 0
 * usuarios activos con Usuarios y permisos = Editar. Se llama dentro de la transacción, después
 * de aplicar el cambio: si falla, la excepción revierte todo.
 */
final class GuardaAdministradores
{
    public const SIN_ADMINISTRADOR = 'Tiene que quedar al menos una persona que pueda administrar usuarios y permisos.';

    public function asegurar(string $campo): void
    {
        $quedaAlguno = User::query()
            ->where('activo', true)
            ->whereHas('sector', fn (Builder $sector): Builder => $sector
                ->where('activo', true)
                ->whereHas('permisos', fn (Builder $permiso): Builder => $permiso
                    ->where('nivel', Nivel::Editar->value)
                    ->whereHas('funcion', fn (Builder $funcion): Builder => $funcion
                        ->where('clave', ClaveFuncion::UsuariosPermisos->value))))
            ->exists();

        if (! $quedaAlguno) {
            throw ValidationException::withMessages([$campo => [self::SIN_ADMINISTRADOR]]);
        }
    }
}
