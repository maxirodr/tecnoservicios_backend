<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use Illuminate\Validation\Rules\Password;

/** `POST /api/auth/elegir-contrasena`: link del mail de recuperación o de bienvenida (RN-IDE-07/09). */
final class ElegirContrasenaRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
