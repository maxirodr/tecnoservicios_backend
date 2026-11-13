<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

/** `POST /api/auth/ingresar` (W-16). */
final class IngresarRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'recordarme' => ['sometimes', 'boolean'],
        ];
    }
}
