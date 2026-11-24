<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Middleware;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Carga\AlcanceDeCarga;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La celda de la matriz que habilita la carga, antes de validar el formulario (gastos.md §8, RN-IDE-14):
 * según `origen`, `gastos`, `rendicion` o (retiros) `control_cantidad` en Editar; sin origen (opciones,
 * aviso de factura repetida), cualquiera de las tres. El alcance por obra lo mira el caso de uso.
 */
final readonly class PuedeCargarGastos
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

        $origen = is_string($request->input('origen')) ? Origen::tryFrom($request->string('origen')->toString()) : null;

        if ($origen === null) {
            return $this->acceso->cargaAlgo($user) ? $next($request) : ApiResponse::error(AlcanceDeCarga::SIN_PERMISO, 403);
        }

        if (! $this->acceso->cargaOrigen($user, $origen)) {
            return ApiResponse::error($origen->seCargaDesdeLaRendicion() ? AlcanceDeCarga::RENDICION_AJENA : AlcanceDeCarga::SIN_PERMISO, 403);
        }

        return $next($request);
    }
}
