<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

/** `GET /api/usuarios/opciones?buscar=` (selector de personas). Mismo largo máximo que el listado de usuarios. */
final class OpcionesUsuariosRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:190'],
        ];
    }

    public function buscar(): ?string
    {
        return $this->filled('buscar') ? $this->string('buscar')->toString() : null;
    }
}
