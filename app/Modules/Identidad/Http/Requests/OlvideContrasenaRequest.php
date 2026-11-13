<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

/** `POST /api/auth/olvide-contrasena` (W-16, RN-IDE-06). */
final class OlvideContrasenaRequest extends IdentidadRequest
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
