<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

/** `GET /api/gastos/filtros/obras?buscar=` y `…/filtros/proveedores?buscar=` (filtros de W-33, DU-G12). */
final class BuscarEnFiltroRequest extends GastosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:'.BuscarObrasRequest::LARGO_BUSQUEDA]];
    }

    public function buscar(): ?string
    {
        return self::texto($this->input('buscar'));
    }
}
