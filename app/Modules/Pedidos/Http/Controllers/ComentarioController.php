<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Comentarios\ComentarPedidoAction;
use App\Modules\Pedidos\Http\Requests\ComentarRequest;
use App\Modules\Pedidos\Http\Resources\ComentarioResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** F-30 (W-09, W-42): `POST /api/pedidos/{id}/comentarios`. No hay rutas para editar ni borrar comentarios. */
final class ComentarioController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function store(ComentarRequest $request, int $pedido, ComentarPedidoAction $comentar): JsonResponse
    {
        return $this->created(new ComentarioResource($comentar->handle($this->usuario($request), $pedido, $request->texto())));
    }
}
