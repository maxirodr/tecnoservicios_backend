<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Correccion\AnularGastoAction;
use App\Modules\Gastos\Application\Lecturas\LecturaDeGasto;
use App\Modules\Gastos\Application\Lecturas\TextoDeHistorial;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Http\Requests\AnularGastoRequest;
use App\Modules\Gastos\Http\Resources\GastoDetalleResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** F-46 · W-34 `POST /api/gastos/{id}/anular` (RN-GAS-40, 41). */
final class AnularGastoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(AnularGastoRequest $request, int $gasto, AnularGastoAction $anular, BuscarGasto $buscar, LecturaDeGasto $lectura, Rubros $rubros): JsonResponse
    {
        $user = $this->usuario($request);
        $anulado = $anular->handle($user, $gasto, $request->motivo());
        $detalle = $buscar->visible($user, $gasto, LecturaDeGasto::DETALLE);

        return $this->ok(
            GastoDetalleResource::detalle($lectura->de($user, $detalle), $rubros),
            'Anulamos '.TextoDeHistorial::queSeCargo($anulado->origen).'.',
        );
    }
}
