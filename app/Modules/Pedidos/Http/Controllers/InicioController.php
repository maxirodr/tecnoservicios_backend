<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Lecturas\InicioQuery;
use App\Modules\Pedidos\Application\Lecturas\ListasDeInicio;
use App\Modules\Pedidos\Application\Lecturas\UltimosMovimientos;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Http\Requests\ListaDeInicioRequest;
use App\Modules\Pedidos\Http\Resources\PedidoFilaResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-04 · F-05 · F-07 (W-01, W-27, W-28, W-41): las pestañas de Inicio. Solo lo propio; cualquier usuario activo. */
final class InicioController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(private readonly ListasDeInicio $listas) {}

    public function inicio(Request $request, InicioQuery $inicio): JsonResponse
    {
        return $this->ok($inicio->handle($this->usuario($request)));
    }

    public function misTareas(ListaDeInicioRequest $request): JsonResponse
    {
        $user = $this->usuario($request);
        $pagina = $this->listas->misTareas($user, $request->obra(), $request->estado(), $request->buscar(), $request->pagina());

        return ApiResponse::paginated(PedidoFilaResource::collection($pagina), null, $this->listas->contadoresDeMisTareas($user));
    }

    public function quePedi(ListaDeInicioRequest $request, UltimosMovimientos $movimientos): JsonResponse
    {
        $pagina = $this->listas->quePedi($this->usuario($request), $request->estado(), $request->pagina());
        $ultimos = $movimientos->de($pagina->items());
        $filas = array_map(static fn (Pedido $p): array => [
            ...PedidoFilaResource::fila($p),
            'ultimo_movimiento' => $ultimos[$p->id] ?? null,
        ], $pagina->items());

        return ApiResponse::paginated($pagina, $filas);
    }

    public function queSigo(ListaDeInicioRequest $request): JsonResponse
    {
        return $this->ok(PedidoFilaResource::collection($this->listas->queSigo($this->usuario($request), $request->pagina())));
    }
}
