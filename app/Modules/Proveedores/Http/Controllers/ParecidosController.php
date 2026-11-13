<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Controllers;

use App\Modules\Proveedores\Application\ProveedoresParecidosQuery;
use App\Modules\Proveedores\Http\Requests\ParecidosRequest;
use App\Modules\Proveedores\Http\Resources\ParecidoResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** `GET /api/proveedores/parecidos` (RN-PRV-08/09): "Parecidos ya cargados" de W-38. No bloquea. */
final class ParecidosController
{
    use RespondsWithEnvelope;

    public function __invoke(ParecidosRequest $request, ProveedoresParecidosQuery $parecidos): JsonResponse
    {
        return $this->ok(ParecidoResource::collection(
            $parecidos->handle($request->nombre(), $request->cuit(), $request->excluirId()),
        ));
    }
}
