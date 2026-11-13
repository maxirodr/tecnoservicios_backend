<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Auth\IngresarAction;
use App\Modules\Identidad\Application\Auth\SesionWeb;
use App\Modules\Identidad\Application\Perfil\UsuarioActualQuery;
use App\Modules\Identidad\Http\Requests\IngresarRequest;
use App\Modules\Identidad\Http\Resources\UsuarioActualResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-01 · W-16: ingresar y salir (Sanctum SPA, cookie de sesión). */
final class IngresoController
{
    use RespondsWithEnvelope;

    public function store(IngresarRequest $request, IngresarAction $ingresar, UsuarioActualQuery $usuarioActual): JsonResponse
    {
        $user = $ingresar->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('recordarme'),
        );

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->ok(new UsuarioActualResource($usuarioActual->handle($user)));
    }

    public function destroy(Request $request, SesionWeb $sesion): JsonResponse
    {
        $sesion->cerrar();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->noContent();
    }
}
