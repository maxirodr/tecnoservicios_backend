<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Middleware;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias `permiso:<funcion>,<nivel>` (p. ej. `permiso:usuarios_permisos,editar`): cada endpoint
 * vuelve a verificar la celda de la matriz (RN-IDE-14, ADR-006). Sin permiso → 403 con el sobre.
 * Lo pueden usar todos los módulos en sus rutas.
 */
final class VerificarPermiso
{
    public function __construct(private readonly Permisos $permisos) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $funcion, string $nivel): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        if (! $this->permisos->puede($user, $funcion, Nivel::from($nivel))) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
