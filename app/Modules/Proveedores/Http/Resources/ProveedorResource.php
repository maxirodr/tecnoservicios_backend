<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Resources;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha del proveedor (W-14 derecha, proveedores.md §10.1). `puede` decide el botón Editar (el
 * genérico no se edita, RN-PRV-13) y si se muestra "Gastado por obra" (RN-PRV-16). No hay saldos,
 * cuenta corriente, adjuntos ni links (D-040, §4.2).
 *
 * @property Proveedor $resource
 */
final class ProveedorResource extends JsonResource
{
    private bool $puedeEditar = false;

    private bool $puedeVerGastado = false;

    public static function conPermisos(Proveedor $proveedor, bool $puedeEditar, bool $puedeVerGastado): self
    {
        $resource = new self($proveedor);
        $resource->puedeEditar = $puedeEditar && ! $proveedor->es_generico;
        $resource->puedeVerGastado = $puedeVerGastado;

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $proveedor = $this->resource;

        return [
            ...ProveedorResumenResource::resumen($proveedor),
            'telefono' => $proveedor->telefono,
            'email' => $proveedor->email,
            'puede' => ['editar' => $this->puedeEditar, 'ver_gastado' => $this->puedeVerGastado],
        ];
    }
}
