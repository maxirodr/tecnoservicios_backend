<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Lecturas\DetallePedidoQuery;
use App\Modules\Pedidos\Application\Pedidos\ActualizarPedidoAction;
use App\Modules\Pedidos\Application\Pedidos\CrearPedidoAction;
use App\Modules\Pedidos\Http\Requests\ActualizarPedidoRequest;
use App\Modules\Pedidos\Http\Requests\CrearPedidoRequest;
use App\Modules\Pedidos\Http\Resources\PedidoDetalleResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-24 · F-33 · F-30 (W-08, W-09, W-32, W-43): crear, ver y editar un pedido. No hay ruta para borrar (RN-PED-30). */
final class PedidoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function store(CrearPedidoRequest $request, CrearPedidoAction $crear, DetallePedidoQuery $detalle): JsonResponse
    {
        $user = $this->usuario($request);
        $resultado = $crear->handle($user, $request->datos());

        return $this->created(new PedidoDetalleResource($detalle->handle($user, $resultado->pedido->id, $resultado->avisados)), 'Creamos el pedido.');
    }

    public function show(Request $request, int $pedido, DetallePedidoQuery $detalle): JsonResponse
    {
        return $this->ok(new PedidoDetalleResource($detalle->handle($this->usuario($request), $pedido)));
    }

    public function update(ActualizarPedidoRequest $request, int $pedido, ActualizarPedidoAction $actualizar, DetallePedidoQuery $detalle): JsonResponse
    {
        $user = $this->usuario($request);
        $actualizar->handle($user, $pedido, $request->cambios(), $request->versionEsperada());

        return $this->ok(new PedidoDetalleResource($detalle->handle($user, $pedido)), 'Guardamos los cambios.');
    }
}
