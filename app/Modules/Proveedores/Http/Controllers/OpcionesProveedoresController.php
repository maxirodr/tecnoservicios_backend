<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Controllers;

use App\Modules\Proveedores\Application\OpcionesProveedoresQuery;
use App\Modules\Proveedores\Http\Requests\OpcionesProveedoresRequest;
use App\Modules\Proveedores\Http\Resources\ProveedorResumenResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** `GET /api/proveedores/opciones` (F-42): combobox de la factura, máx. 20. */
final class OpcionesProveedoresController
{
    use RespondsWithEnvelope;

    public function __invoke(OpcionesProveedoresRequest $request, OpcionesProveedoresQuery $opciones): JsonResponse
    {
        return $this->ok(ProveedorResumenResource::collection($opciones->handle($request->buscar())));
    }
}
