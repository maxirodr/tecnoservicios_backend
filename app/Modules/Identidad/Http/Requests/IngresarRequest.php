<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Application\Auth\IngresarAction;
use App\Modules\Identidad\Domain\Models\CodigoIngreso;

/** `POST /api/auth/ingresar` (W-16, ADR-015): mail + código de 6 dígitos. */
final class IngresarRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'codigo' => ['required', 'digits:'.CodigoIngreso::DIGITOS],
        ];
    }

    /**
     * Un código con otra forma recibe el mismo mensaje que uno incorrecto (no gasta un intento).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'codigo.required' => IngresarAction::CODIGO_INVALIDO,
            'codigo.digits' => IngresarAction::CODIGO_INVALIDO,
        ];
    }
}
