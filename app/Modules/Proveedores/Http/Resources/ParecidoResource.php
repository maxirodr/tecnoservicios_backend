<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Resources;

use App\Modules\Proveedores\Application\Parecido;
use App\Modules\Proveedores\Domain\Cuit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * "Parecidos ya cargados" (RN-PRV-08): `{id, nombre, cuit, motivo: "nombre"|"cuit"}`.
 *
 * @property Parecido $resource
 */
final class ParecidoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $proveedor = $this->resource->proveedor;

        return [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'cuit' => $proveedor->cuit === null ? null : Cuit::formatear($proveedor->cuit),
            'motivo' => $this->resource->motivo,
        ];
    }
}
