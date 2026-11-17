<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

use App\Shared\Http\RecortaBusqueda;

/** `GET /api/proveedores?buscar=&page=` (W-14). Más largo que el máximo se recorta (PU-118). */
final class BuscarProveedoresRequest extends ProveedoresRequest
{
    use RecortaBusqueda;

    private const LARGO_MAXIMO = 150;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:'.self::LARGO_MAXIMO]];
    }

    public function buscar(): ?string
    {
        return $this->textoOpcional('buscar');
    }

    protected function prepareForValidation(): void
    {
        $this->recortarBusqueda('buscar', self::LARGO_MAXIMO);
    }
}
