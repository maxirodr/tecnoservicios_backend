<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;
use Illuminate\Validation\Rule;

/**
 * `GET /api/obras/{id}/rendicion/por-proveedor?rubro=&desde=&hasta=` (W-37, RN-REN-37): `rubro` es uno de los
 * 12 del resumen (INSUMOS incluye EPP); `desde`/`hasta` por fecha efectiva, AAAA-MM-DD, hasta ≥ desde.
 */
final class PorProveedorRequest extends LecturaDeRendicionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $claves = array_map(static fn (RubroDelCatalogo $r): string => $r->clave, $this->container->make(CatalogoDeRubros::class)->presupuestables());

        return [
            'rubro' => ['sometimes', 'nullable', Rule::in($claves)],
            'desde' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'hasta' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'hasta.after_or_equal' => "El 'hasta' no puede ser anterior al 'desde'."];
    }

    public function rubro(): ?string
    {
        return $this->textoONull('rubro');
    }

    public function desde(): ?string
    {
        return $this->textoONull('desde');
    }

    public function hasta(): ?string
    {
        return $this->textoONull('hasta');
    }
}
