<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Lecturas\GastoLeido;
use App\Modules\Gastos\Application\Lecturas\ListarGastosQuery;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Http\Requests\ListarGastosRequest;
use App\Modules\Gastos\Http\Resources\GastoFilaResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/**
 * F-45 · W-33 `GET /api/gastos`: `[GastoFila]` de a 50 con `meta: {total_filtrado, comprobantes, anulados,
 * desde, hasta}` y, con `agrupar`, `meta.grupos: [{clave, nombre, subtotal, comprobantes, filas}]`.
 */
final class GastosCargadosController
{
    use UsuarioAutenticado;

    public function __invoke(ListarGastosRequest $request, ListarGastosQuery $listar, Rubros $rubros): JsonResponse
    {
        $resultado = $listar->handle($this->usuario($request), $request->filtros());
        $meta = $resultado['meta'];

        if (isset($meta['grupos']) && is_array($meta['grupos'])) {
            $meta['grupos'] = array_map(static fn (array $g): array => [
                ...$g,
                'filas' => array_map(static fn (array $f): array => GastoFilaResource::fila($f['leido'], $rubros, $f['parte']), $g['filas']),
            ], $meta['grupos']);
        }

        return ApiResponse::paginated(
            $resultado['pagina']->withQueryString(),
            array_map(static fn (GastoLeido $l): array => GastoFilaResource::fila($l, $rubros), $resultado['filas']),
            $meta,
        );
    }
}
