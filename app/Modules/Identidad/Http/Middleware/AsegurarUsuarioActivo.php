<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Middleware;

use App\Modules\Identidad\Application\Auth\SesionWeb;
use App\Modules\Identidad\Domain\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Va en el grupo `api`: si la persona de la sesión quedó inactiva (RN-IDE-04, HU-006), se
 * cierra su sesión en el próximo pedido y responde 401.
 */
final class AsegurarUsuarioActivo
{
    public function __construct(private readonly SesionWeb $sesion) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->activo) {
            $this->sesion->cerrar();

            if ($request->hasSession()) {
                $request->session()->invalidate();
            }

            throw new AuthenticationException;
        }

        return $next($request);
    }
}
