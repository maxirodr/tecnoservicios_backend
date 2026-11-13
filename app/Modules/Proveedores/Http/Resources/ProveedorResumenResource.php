<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Resources;

use App\Modules\Proveedores\Domain\Cuit;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proveedor en una lista: W-14 (nombre, CUIT y rubro habitual si los tiene, marca "genérico") y el
 * combobox de la factura (F-42). Sin saldos ni cuenta corriente (D-040).
 *
 * @property Proveedor $resource
 */
final class ProveedorResumenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return self::resumen($this->resource);
    }

    /**
     * @return array{id: int, nombre: string, cuit: string|null, rubro_habitual: array{clave: string, nombre: string}|null, es_generico: bool}
     */
    public static function resumen(Proveedor $proveedor): array
    {
        return [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'cuit' => $proveedor->cuit === null ? null : Cuit::formatear($proveedor->cuit),
            'rubro_habitual' => self::rubro($proveedor->rubroHabitual),
            'es_generico' => $proveedor->es_generico,
        ];
    }

    /**
     * @return array{clave: string, nombre: string}|null
     */
    private static function rubro(?Rubro $rubro): ?array
    {
        return $rubro === null ? null : ['clave' => $rubro->clave, 'nombre' => $rubro->nombre];
    }
}
