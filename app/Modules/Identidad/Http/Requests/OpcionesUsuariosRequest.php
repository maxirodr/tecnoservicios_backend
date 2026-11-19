<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Shared\Http\RecortaBusqueda;

/**
 * `GET /api/usuarios/opciones?buscar=&sector_id=` (selector de personas). Mismo largo máximo que el listado de
 * usuarios; más largo se recorta, nunca 422 (PU-118).
 */
final class OpcionesUsuariosRequest extends IdentidadRequest
{
    use RecortaBusqueda;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:'.ListarUsuariosRequest::LARGO_BUSCAR],
            'sector_id' => ['nullable', 'integer'],
        ];
    }

    public function buscar(): ?string
    {
        return $this->filled('buscar') ? $this->string('buscar')->toString() : null;
    }

    /** Propietario por sector al cargar un pedido (pedidos.md RN-PED-03, resuelta DU-P20). */
    public function sectorId(): ?int
    {
        return $this->filled('sector_id') ? $this->integer('sector_id') : null;
    }

    protected function prepareForValidation(): void
    {
        $this->recortarBusqueda('buscar', ListarUsuariosRequest::LARGO_BUSCAR);
    }
}
