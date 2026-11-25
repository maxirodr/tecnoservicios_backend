<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Middleware;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `gastos` ≥ ver (W-33, W-34, historial y filtros), como `permiso:gastos,ver`, pero con el 403 de la
 * spec: "No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia." (gastos.md §7). El alcance por
 * obra lo miran los casos de uso.
 */
final readonly class VerGastos
{
    public const SIN_ACCESO = 'No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia.';

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

        if (! $this->acceso->puede($user, ClaveFuncion::Gastos, Nivel::Ver)) {
            return ApiResponse::error(self::SIN_ACCESO, 403);
        }

        return $next($request);
    }
}
