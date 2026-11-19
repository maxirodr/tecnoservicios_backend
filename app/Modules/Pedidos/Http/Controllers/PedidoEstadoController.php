<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Estados\BloquearPedidoAction;
use App\Modules\Pedidos\Application\Estados\CompletarPedidoAction;
use App\Modules\Pedidos\Application\Estados\ReabrirPedidoAction;
use App\Modules\Pedidos\Application\Estados\ReasignarPedidoAction;
use App\Modules\Pedidos\Application\Estados\RetomarPedidoAction;
use App\Modules\Pedidos\Application\Estados\TomarPedidoAction;
use App\Modules\Pedidos\Application\Lecturas\DetallePedidoQuery;
use App\Modules\Pedidos\Http\Requests\BloquearRequest;
use App\Modules\Pedidos\Http\Requests\CompletarRequest;
use App\Modules\Pedidos\Http\Requests\ReabrirRequest;
use App\Modules\Pedidos\Http\Requests\ReasignarRequest;
use App\Modules\Pedidos\Http\Resources\PedidoDetalleResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-28 · F-29 · F-31 · F-32 · F-65 (W-09, W-29…W-31, W-42): transiciones y reasignar (§6.1). Responden `PedidoDetalle`. */
final class PedidoEstadoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(private readonly DetallePedidoQuery $detalle) {}

    public function tomar(Request $request, int $pedido, TomarPedidoAction $tomar): JsonResponse
    {
        $tomar->handle($this->usuario($request), $pedido);

        return $this->detalle($request, $pedido, 'Lo tomaste: quedó En curso.');
    }

    public function bloquear(BloquearRequest $request, int $pedido, BloquearPedidoAction $bloquear): JsonResponse
    {
        $bloquear->handle($this->usuario($request), $pedido, $request->texto());

        return $this->detalle($request, $pedido, 'Quedó Bloqueado.');
    }

    public function retomar(Request $request, int $pedido, RetomarPedidoAction $retomar): JsonResponse
    {
        $retomar->handle($this->usuario($request), $pedido);

        return $this->detalle($request, $pedido, 'Lo retomaste: quedó En curso.');
    }

    public function completar(CompletarRequest $request, int $pedido, CompletarPedidoAction $completar): JsonResponse
    {
        $completar->handle($this->usuario($request), $pedido, $request->texto(), $request->comprobantes());

        return $this->detalle($request, $pedido, 'Quedó Completado.');
    }

    public function reabrir(ReabrirRequest $request, int $pedido, ReabrirPedidoAction $reabrir): JsonResponse
    {
        $reabrir->handle($this->usuario($request), $pedido, $request->texto(), $request->propietarioId(), $request->sectorId());

        return $this->detalle($request, $pedido, 'Lo reabriste.');
    }

    public function reasignar(ReasignarRequest $request, int $pedido, ReasignarPedidoAction $reasignar): JsonResponse
    {
        $user = $this->usuario($request);
        $resultado = $reasignar->handle($user, $pedido, $request->propietarioId(), $request->sectorId(), $request->comentario());

        return $this->ok(new PedidoDetalleResource($this->detalle->handle($user, $pedido, $resultado->avisados, true)), 'Lo reasignaste.');
    }

    private function detalle(Request $request, int $pedido, string $mensaje): JsonResponse
    {
        return $this->ok(new PedidoDetalleResource($this->detalle->handle($this->usuario($request), $pedido, null, true)), $mensaje);
    }
}
