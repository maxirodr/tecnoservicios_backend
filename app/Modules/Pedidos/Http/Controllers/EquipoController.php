<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Equipos\ActualizarEquipoAction;
use App\Modules\Pedidos\Application\Equipos\CrearEquipoAction;
use App\Modules\Pedidos\Application\Equipos\ListarEquiposQuery;
use App\Modules\Pedidos\Application\Equipos\QuitarEquipoAction;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Modules\Pedidos\Http\Requests\EquipoRequest;
use App\Modules\Pedidos\Http\Requests\ListarEquiposRequest;
use App\Modules\Pedidos\Http\Resources\EquipoResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/** F-67 (W-49): equipos en reparación. Ver y editar por la fila `equipos_reparacion` de la matriz (rutas). */
final class EquipoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function index(ListarEquiposRequest $request, ListarEquiposQuery $equipos): JsonResponse
    {
        return ApiResponse::paginated(EquipoResource::collection($equipos->handle($request->estado(), $request->pagina())));
    }

    public function store(EquipoRequest $request, CrearEquipoAction $crear): JsonResponse
    {
        $equipo = $crear->handle($this->usuario($request), $request->datos());

        return $this->created(new EquipoResource($equipo->load(EquipoResource::CON)), 'Agregamos el equipo.');
    }

    public function update(EquipoRequest $request, int $equipo, ActualizarEquipoAction $actualizar): JsonResponse
    {
        $actualizado = $actualizar->handle($this->usuario($request), $this->buscar($equipo), $request->datos(), $request->versionEsperada());

        return $this->ok(new EquipoResource($actualizado->load(EquipoResource::CON)), 'Guardamos los cambios.');
    }

    public function destroy(int $equipo, QuitarEquipoAction $quitar): JsonResponse
    {
        $quitar->handle($this->buscar($equipo)->id);

        return $this->ok(null, 'Quitamos el equipo.');
    }

    /**
     * @throws HttpResponseException 404
     */
    private function buscar(int $id): EquipoReparacion
    {
        $equipo = EquipoReparacion::query()->find($id);

        if (! $equipo instanceof EquipoReparacion) {
            throw new HttpResponseException(ApiResponse::error('Equipo no encontrado.', 404));
        }

        return $equipo;
    }
}
