<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

/** `POST /api/auth/pedir-codigo` (W-16, ADR-015, RN-IDE-02). */
final class PedirCodigoRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
        ];
    }
}
