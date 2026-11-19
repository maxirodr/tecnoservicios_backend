<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Seguidores\DejarDeSeguirPedidoAction;
use App\Modules\Pedidos\Application\Seguidores\SeguirPedidoAction;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-07 (W-28, W-09): `POST` y `DELETE /api/pedidos/{id}/seguir` → `{sigo}`. */
final class SeguirController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function store(Request $request, int $pedido, SeguirPedidoAction $seguir): JsonResponse
    {
        return $this->ok(['sigo' => $seguir->handle($this->usuario($request), $pedido)], 'Lo seguís: está en Tareas que sigo.');
    }

    public function destroy(Request $request, int $pedido, DejarDeSeguirPedidoAction $dejar): JsonResponse
    {
        return $this->ok(['sigo' => $dejar->handle($this->usuario($request), $pedido)], 'Dejaste de seguirlo.');
    }
}
