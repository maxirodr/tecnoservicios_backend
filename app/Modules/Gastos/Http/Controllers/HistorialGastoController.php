<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Lecturas\HistorialDeGastoQuery;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-14 · F-46 · W-34 `GET /api/gastos/{id}/historial` (RN-GAS-42). */
final class HistorialGastoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(Request $request, int $gasto, HistorialDeGastoQuery $historial): JsonResponse
    {
        return $this->ok($historial->handle($this->usuario($request), $gasto));
    }
}
