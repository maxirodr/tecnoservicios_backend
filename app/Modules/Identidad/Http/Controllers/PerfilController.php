<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Auth\SesionWeb;
use App\Modules\Identidad\Application\Perfil\ActualizarPerfilAction;
use App\Modules\Identidad\Application\Perfil\CambiarContrasenaAction;
use App\Modules\Identidad\Application\Perfil\UsuarioActualQuery;
use App\Modules\Identidad\Http\Controllers\Concerns\UsuarioAutenticado;
use App\Modules\Identidad\Http\Requests\ActualizarPerfilRequest;
use App\Modules\Identidad\Http\Requests\CambiarContrasenaRequest;
use App\Modules\Identidad\Http\Resources\UsuarioActualResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-02 · F-10 · W-19: `GET/PATCH /api/me` y cambiar mi contraseña. */
final class PerfilController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function show(Request $request, UsuarioActualQuery $usuarioActual): JsonResponse
    {
        return $this->ok(new UsuarioActualResource($usuarioActual->handle($this->usuario($request))));
    }

    public function update(ActualizarPerfilRequest $request, ActualizarPerfilAction $actualizar, UsuarioActualQuery $usuarioActual): JsonResponse
    {
        $user = $actualizar->handle($this->usuario($request), $request->datos());

        return $this->ok(new UsuarioActualResource($usuarioActual->handle($user)), 'Guardamos tus datos.');
    }

    public function cambiarContrasena(CambiarContrasenaRequest $request, CambiarContrasenaAction $cambiar, SesionWeb $sesion): JsonResponse
    {
        $cambiar->handle(
            $this->usuario($request),
            $request->string('actual')->toString(),
            $request->string('nueva')->toString(),
            $request->cookies->has($sesion->cookieRecordarme()),
        );

        return $this->noContent('Cambiamos tu contraseña.');
    }
}
