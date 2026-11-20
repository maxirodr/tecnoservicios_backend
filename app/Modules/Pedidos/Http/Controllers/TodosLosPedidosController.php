<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Lecturas\TodosLosPedidos;
use App\Modules\Pedidos\Http\Requests\TodosLosPedidosRequest;
use App\Modules\Pedidos\Http\Resources\PedidoFilaResource;
use App\Modules\Pedidos\Infrastructure\Exportacion\PedidosAExcel;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * F-35 · F-36 · F-17 (W-24, W-25, pestaña Pedidos de W-07): Todos los pedidos con filtros, paginado de a
 * 50, con "N de M pedidos con estos filtros" (`meta.total` de `meta.total_visibles`), y exportar a Excel.
 */
final class TodosLosPedidosController
{
    use UsuarioAutenticado;

    public function __construct(private readonly TodosLosPedidos $pedidos) {}

    public function index(TodosLosPedidosRequest $request): JsonResponse
    {
        $user = $this->usuario($request);
        $pagina = $this->pedidos->pagina($user, $request->filtros(), $request->pagina());

        return ApiResponse::paginated(PedidoFilaResource::collection($pagina), null, ['total_visibles' => $this->pedidos->totalVisibles($user)]);
    }

    /** Todo lo filtrado (no solo la página), con el orden elegido; es un archivo, no envelope (§11.1). */
    public function exportar(TodosLosPedidosRequest $request, PedidosAExcel $excel): StreamedResponse
    {
        $pedidos = $this->pedidos->todos($this->usuario($request), $request->filtros());

        return response()->streamDownload(
            static fn () => $excel->escribir($pedidos, 'php://output'),
            PedidosAExcel::nombreDeArchivo(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
