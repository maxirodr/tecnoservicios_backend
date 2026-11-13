<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-25 (resuelta DU-A09): ningún cambio (baja, cambio de sector o de matriz) puede dejar 0
 * usuarios activos con Usuarios y permisos = Editar. Dentro de la transacción: `bloquear()` antes de
 * aplicar el cambio y `asegurar()` después; si falla, la excepción revierte todo.
 *
 * SEG-12: `bloquear()` toma con lockForUpdate la fila de la función `usuarios_permisos` (siempre existe,
 * es del catálogo) como cerrojo común. Dos cambios simultáneos corren uno detrás del otro: el segundo
 * ve la baja del primero y no pueden quedar cero administradores. Un solo cerrojo, tomado primero: sin
 * deadlocks entre ellos.
 */
final class GuardaAdministradores
{
    public const SIN_ADMINISTRADOR = 'Tiene que quedar al menos una persona que pueda administrar usuarios y permisos.';

    public function bloquear(): void
    {
        Funcion::query()->where('clave', ClaveFuncion::UsuariosPermisos->value)->lockForUpdate()->first();
    }

    public function asegurar(string $campo): void
    {
        // Lectura con bloqueo: ve lo último confirmado, no la foto del inicio de la transacción.
        $administradores = User::query()
            ->where('activo', true)
            ->whereHas('sector', fn (Builder $sector): Builder => $sector
                ->where('activo', true)
                ->whereHas('permisos', fn (Builder $permiso): Builder => $permiso
                    ->where('nivel', Nivel::Editar->value)
                    ->whereHas('funcion', fn (Builder $funcion): Builder => $funcion
                        ->where('clave', ClaveFuncion::UsuariosPermisos->value))))
            ->sharedLock()
            ->count();

        if ($administradores === 0) {
            throw ValidationException::withMessages([$campo => [self::SIN_ADMINISTRADOR]]);
        }
    }
}
