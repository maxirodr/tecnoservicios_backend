<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Lecturas\GastoLeido;
use App\Modules\Gastos\Application\Lecturas\RenglonesSinDocumentoQuery;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Http\Resources\GastoDetalleResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** W-46 `GET /api/obras/{obra}/renglones-sin-documento` (RN-GAS-22, gastos.md §12.1; ruta del módulo Gastos). */
final class RenglonesSinDocumentoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(Request $request, int $obra, RenglonesSinDocumentoQuery $query, Rubros $rubros): JsonResponse
    {
        $w46 = $query->handle($this->usuario($request), $obra);
        $detalle = static fn (GastoLeido $l): array => GastoDetalleResource::detalle($l, $rubros);

        return $this->ok([
            'obra' => [
                'id' => $w46['obra']->id,
                'numero' => $w46['obra']->numero,
                'nombre_trabajo' => $w46['obra']->nombreTrabajo,
                'estado' => $w46['obra']->estado,
            ],
            'amortizacion' => $w46['amortizacion'] === null ? null : $detalle($w46['amortizacion']),
            'retiros' => array_map($detalle, $w46['retiros']),
            'devoluciones' => array_map($detalle, $w46['devoluciones']),
            'periodos' => array_map($detalle, $w46['periodos']),
            'ultimo_proveedor_retiro' => $w46['ultimo_proveedor_retiro'],
            'puede' => $w46['puede'],
        ]);
    }
}
