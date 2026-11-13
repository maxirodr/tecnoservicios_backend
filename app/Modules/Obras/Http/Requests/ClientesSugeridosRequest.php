<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

/** `GET /api/obras/clientes?buscar=` (RN-OBR-17). Mismo largo máximo que el buscador del listado. */
final class ClientesSugeridosRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }
}
