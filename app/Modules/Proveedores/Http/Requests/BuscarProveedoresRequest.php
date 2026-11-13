<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

/** `GET /api/proveedores?buscar=&page=` (W-14). */
final class BuscarProveedoresRequest extends ProveedoresRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:150']];
    }

    public function buscar(): ?string
    {
        return $this->textoOpcional('buscar');
    }
}
