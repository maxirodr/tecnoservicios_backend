<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Lecturas\OpcionesDeFiltrosQuery;
use App\Modules\Gastos\Http\Requests\BuscarEnFiltroRequest;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** Opciones de los filtros Obra y Proveedor de W-33 para quien ve (gastos.md §12.1, resuelta DU-G12). */
final class FiltrosDeGastosController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function obras(BuscarEnFiltroRequest $request, OpcionesDeFiltrosQuery $opciones): JsonResponse
    {
        return $this->ok($opciones->obras($this->usuario($request), $request->buscar()));
    }

    public function proveedores(BuscarEnFiltroRequest $request, OpcionesDeFiltrosQuery $opciones): JsonResponse
    {
        return $this->ok($opciones->proveedores($this->usuario($request), $request->buscar()));
    }
}
