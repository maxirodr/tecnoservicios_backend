<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Usuarios\ACargoDelUsuarioQuery;
use App\Modules\Identidad\Application\Usuarios\ActualizarUsuarioAction;
use App\Modules\Identidad\Application\Usuarios\CrearUsuarioAction;
use App\Modules\Identidad\Application\Usuarios\ListarUsuariosQuery;
use App\Modules\Identidad\Application\Usuarios\OpcionesUsuariosQuery;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Http\Controllers\Concerns\UsuarioAutenticado;
use App\Modules\Identidad\Http\Requests\ActualizarUsuarioRequest;
use App\Modules\Identidad\Http\Requests\CrearUsuarioRequest;
use App\Modules\Identidad\Http\Requests\ListarUsuariosRequest;
use App\Modules\Identidad\Http\Resources\UsuarioOpcionResource;
use App\Modules\Identidad\Http\Resources\UsuarioResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-58 · F-61 · W-15 · W-39: usuarios. La autorización (matriz) va en las rutas. */
final class UsuarioController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function index(ListarUsuariosRequest $request, ListarUsuariosQuery $listar): JsonResponse
    {
        return $this->ok(UsuarioResource::collection(
            $listar->handle($request->buscar(), $request->sectorId(), $request->activo()),
        ));
    }

    public function opciones(Request $request, OpcionesUsuariosQuery $opciones): JsonResponse
    {
        $buscar = $request->filled('buscar') ? $request->string('buscar')->toString() : null;

        return $this->ok(UsuarioOpcionResource::collection($opciones->handle($buscar)));
    }

    public function store(CrearUsuarioRequest $request, CrearUsuarioAction $crear): JsonResponse
    {
        $user = $crear->handle($request->datos());

        // ADR-015: no hay mail de bienvenida; la persona entra pidiendo un código con su mail.
        return $this->created(new UsuarioResource($user->load(['sector', 'jefe'])), "Listo: {$user->nombre} ya puede entrar con su mail.");
    }

    public function show(int $usuario, ACargoDelUsuarioQuery $aCargo): JsonResponse
    {
        $user = $this->buscar($usuario);

        return $this->ok(UsuarioResource::detalle($user, $aCargo->handle($user)));
    }

    public function update(ActualizarUsuarioRequest $request, int $usuario, ActualizarUsuarioAction $actualizar, ACargoDelUsuarioQuery $aCargo): JsonResponse
    {
        $user = $actualizar->handle($this->usuario($request), $this->buscar($usuario), $request->datos());
        $user->load(['sector', 'jefe']);

        return $this->ok(UsuarioResource::detalle($user, $aCargo->handle($user)), 'Guardamos los cambios.');
    }

    private function buscar(int $id): User
    {
        return User::query()->with(['sector', 'jefe'])->findOrFail($id);
    }
}
