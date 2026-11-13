<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Estado\CambiarEstadoObraAction;
use App\Modules\Obras\Application\Estado\PedidoAbiertoArray;
use App\Modules\Obras\Application\Obras\FichaObraQuery;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Http\Controllers\Concerns\ConUsuario;
use App\Modules\Obras\Http\Requests\CambiarEstadoRequest;
use App\Modules\Obras\Http\Resources\ObraFichaResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-21 (W-22): pedidos abiertos de la obra y cambio de estado. */
final class ObraEstadoController
{
    use ConUsuario;
    use RespondsWithEnvelope;

    public function pedidosAbiertos(Request $request, int $obra, BuscarObra $buscar, PedidosDeObra $pedidos): JsonResponse
    {
        $visible = $buscar->visible($this->usuario($request), $obra);

        return $this->ok(array_map(PedidoAbiertoArray::de(...), $pedidos->abiertosDe($visible->id)));
    }

    public function store(CambiarEstadoRequest $request, int $obra, BuscarObra $buscar, CambiarEstadoObraAction $cambiar, FichaObraQuery $ficha): JsonResponse
    {
        $user = $this->usuario($request);
        $cambiar->handle($user, $buscar->visible($user, $obra), $request->estado(), $request->pedidosConfirmados());

        return $this->ok(new ObraFichaResource($ficha->handle($user, $buscar->visible($user, $obra))), 'Cambiamos el estado de la obra.');
    }
}
