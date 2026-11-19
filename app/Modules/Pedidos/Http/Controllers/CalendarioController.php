<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Lecturas\CalendarioDePedidos;
use App\Modules\Pedidos\Http\Requests\CalendarioRequest;
use App\Modules\Pedidos\Http\Resources\PedidoCalendarioResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** F-11 (W-03): el calendario de un mes o una semana. El rango acotado (62 días) hace de página. */
final class CalendarioController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(CalendarioRequest $request, CalendarioDePedidos $calendario): JsonResponse
    {
        $pedidos = $calendario->handle($this->usuario($request), $request->desde(), $request->hasta(), $request->de(), $request->obra());

        return $this->ok(PedidoCalendarioResource::collection($pedidos));
    }
}
