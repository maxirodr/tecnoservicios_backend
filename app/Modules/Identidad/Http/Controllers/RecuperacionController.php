<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Auth\ElegirContrasenaAction;
use App\Modules\Identidad\Application\Auth\SolicitarRecuperacionAction;
use App\Modules\Identidad\Application\Perfil\UsuarioActualQuery;
use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Http\Requests\ElegirContrasenaRequest;
use App\Modules\Identidad\Http\Requests\OlvideContrasenaRequest;
use App\Modules\Identidad\Http\Resources\UsuarioActualResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** F-01 · W-16: "Olvidé mi contraseña" y elegir contraseña desde el link (recuperación o bienvenida). */
final class RecuperacionController
{
    use RespondsWithEnvelope;

    public function store(OlvideContrasenaRequest $request, SolicitarRecuperacionAction $solicitar): JsonResponse
    {
        $email = MailNormalizado::de($request->string('email')->toString());
        $solicitar->handle($email);

        // RN-IDE-06: la misma respuesta exista o no el usuario.
        return $this->ok(null, "Te mandamos un mail a {$email} para elegir una nueva.");
    }

    public function update(ElegirContrasenaRequest $request, ElegirContrasenaAction $elegir, UsuarioActualQuery $usuarioActual): JsonResponse
    {
        $user = $elegir->handle(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $this->ok(new UsuarioActualResource($usuarioActual->handle($user)), 'Listo: ya podés usar tu contraseña nueva.');
    }
}
