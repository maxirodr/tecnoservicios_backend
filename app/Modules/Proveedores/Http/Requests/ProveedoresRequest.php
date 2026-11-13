<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest del módulo: forma en el borde, mensajes en las palabras del cliente. La
 * autorización la hace el middleware `permiso` (matriz, ADR-006) salvo donde se indica.
 */
abstract class ProveedoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Completá este campo.',
            'string' => 'Tiene que ser texto.',
            'email.email' => 'El mail no es válido.',
            'max' => 'Es demasiado largo (máximo :max caracteres).',
            'integer' => 'Tiene que ser un número entero.',
            'exists' => 'No existe o no está disponible.',
        ];
    }

    protected function textoOpcional(string $campo): ?string
    {
        return $this->filled($campo) ? $this->string($campo)->toString() : null;
    }
}
