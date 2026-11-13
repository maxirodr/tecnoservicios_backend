<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Obras\ActualizarObraAction;
use App\Modules\Obras\Application\Obras\ClientesSugeridosQuery;
use App\Modules\Obras\Application\Obras\CrearObraAction;
use App\Modules\Obras\Application\Obras\FichaObraQuery;
use App\Modules\Obras\Application\Obras\ListarObrasQuery;
use App\Modules\Obras\Http\Controllers\Concerns\ConUsuario;
use App\Modules\Obras\Http\Requests\ActualizarObraRequest;
use App\Modules\Obras\Http\Requests\CrearObraRequest;
use App\Modules\Obras\Http\Requests\ListarObrasRequest;
use App\Modules\Obras\Http\Resources\ObraFichaResource;
use App\Modules\Obras\Http\Resources\ObraFilaResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-15 · F-16 · F-17 · F-20 · F-22 (W-05, W-06, W-07, W-23). Permisos por matriz en las rutas. */
final class ObraController
{
    use ConUsuario;
    use RespondsWithEnvelope;

    public function index(ListarObrasRequest $request, ListarObrasQuery $listar): JsonResponse
    {
        $listado = $listar->handle($this->usuario($request), $request->estado(), $request->buscar(), $request->responsableId(), $request->pagina());
        $respuesta = ApiResponse::success(ObraFilaResource::collection($listado->pagina));

        /** @var array{meta: array<string, mixed>} $cuerpo */
        $cuerpo = $respuesta->getData(true);
        $cuerpo['meta']['contadores'] = $listado->contadores;
        $cuerpo['meta']['pedidos_sin_obra_abiertos'] = $listado->pedidosSinObraAbiertos;

        return $respuesta->setData($cuerpo);
    }

    public function clientes(Request $request, ClientesSugeridosQuery $clientes): JsonResponse
    {
        $buscar = $request->filled('buscar') ? $request->string('buscar')->toString() : null;

        return $this->ok($clientes->handle($this->usuario($request), $buscar));
    }

    public function store(CrearObraRequest $request, CrearObraAction $crear, BuscarObra $buscar, FichaObraQuery $ficha): JsonResponse
    {
        $user = $this->usuario($request);
        $obra = $crear->handle($user, $request->datos());

        return $this->created(new ObraFichaResource($ficha->handle($user, $buscar->visible($user, $obra->id))), 'Creamos la obra.');
    }

    public function show(Request $request, int $obra, BuscarObra $buscar, FichaObraQuery $ficha): JsonResponse
    {
        $user = $this->usuario($request);

        return $this->ok(new ObraFichaResource($ficha->handle($user, $buscar->visible($user, $obra))));
    }

    public function update(ActualizarObraRequest $request, int $obra, BuscarObra $buscar, ActualizarObraAction $actualizar, FichaObraQuery $ficha): JsonResponse
    {
        $user = $this->usuario($request);
        $actualizar->handle($user, $buscar->editable($user, $obra), $request->datos(), $request->contactos());

        return $this->ok(new ObraFichaResource($ficha->handle($user, $buscar->visible($user, $obra))), 'Guardamos los cambios.');
    }
}
