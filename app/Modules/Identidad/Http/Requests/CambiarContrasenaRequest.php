<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use Illuminate\Validation\Rules\Password;

/** `PUT /api/me/contrasena` (W-19, RN-IDE-17). */
final class CambiarContrasenaRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual' => ['required', 'string'],
            'nueva' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
