<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Middleware;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `PATCH /api/gastos/{id}`: `gastos = editar` corrige (RN-GAS-37); `control_cantidad = editar` solo asocia o
 * quita el remito de un retiro (gastos.md §4.4). Qué puede cambiar cada uno lo decide el caso de uso.
 */
final readonly class PuedeCorregirGastos
{
    public function __construct(private AccesoGasto $acceso) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        if (! $this->acceso->puede($user, ClaveFuncion::Gastos, Nivel::Editar)
            && ! $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Editar)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
