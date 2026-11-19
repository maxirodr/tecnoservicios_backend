<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Recorrido\RecorridoQuery;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-34 · F-14 (W-09, W-32): `GET /api/pedidos/{id}/recorrido?solo=cambios`. */
final class RecorridoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(Request $request, int $pedido, RecorridoQuery $recorrido): JsonResponse
    {
        return $this->ok($recorrido->handle($this->usuario($request), $pedido, $request->query('solo') === 'cambios'));
    }
}
