<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Controllers;

use App\Modules\Proveedores\Application\GastadoPorObraQuery;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Http\Resources\GastadoPorObraResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** `GET /api/proveedores/{id}/gastado-por-obra` (F-57, RN-PRV-15…17). */
final class GastadoPorObraController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __invoke(Request $request, int $proveedor, GastadoPorObraQuery $gastado): JsonResponse
    {
        $resultado = $gastado->handle($this->usuario($request), Proveedor::query()->findOrFail($proveedor));

        return $this->ok(new GastadoPorObraResource($resultado));
    }
}
