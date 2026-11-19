<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Http\Controllers;

use App\Modules\Avisos\Application\Bandeja\ContarNoLeidosQuery;
use App\Modules\Avisos\Application\Bandeja\ListarAvisosQuery;
use App\Modules\Avisos\Application\Bandeja\MarcarLeidoAction;
use App\Modules\Avisos\Application\Bandeja\MarcarTodosLeidosAction;
use App\Modules\Avisos\Http\Requests\ListarAvisosRequest;
use App\Modules\Avisos\Http\Resources\AvisoResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-08 · F-64 (W-02, W-41, W-45): campana, contador, lista y marcar leído. Cada uno, solo los suyos. */
final class AvisoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function index(ListarAvisosRequest $request, ListarAvisosQuery $listar, ContarNoLeidosQuery $contar): JsonResponse
    {
        $userId = $this->usuario($request)->id;
        $pagina = $listar->handle($userId, $request->leidos(), $request->tipo(), $request->porPagina(), $request->pagina());
        $respuesta = ApiResponse::success(AvisoResource::collection($pagina));

        /** @var array{meta: array<string, mixed>} $cuerpo */
        $cuerpo = $respuesta->getData(true);
        $cuerpo['meta']['no_leidos'] = $contar->handle($userId);

        return $respuesta->setData($cuerpo);
    }

    public function contador(Request $request, ContarNoLeidosQuery $contar): JsonResponse
    {
        return $this->ok(['no_leidos' => $contar->handle($this->usuario($request)->id)]);
    }

    public function leido(Request $request, int $aviso, MarcarLeidoAction $marcar): JsonResponse
    {
        return $this->ok(new AvisoResource($marcar->handle($this->usuario($request)->id, $aviso)));
    }

    public function leerTodos(Request $request, MarcarTodosLeidosAction $marcar): JsonResponse
    {
        return $this->ok(['marcados' => $marcar->handle($this->usuario($request)->id)]);
    }
}
