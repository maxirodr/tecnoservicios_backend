<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest del módulo: valida la forma en el borde con mensajes en las palabras
 * del cliente. La autorización la hace el middleware `permiso` (matriz, ADR-006), no authorize().
 */
abstract class IdentidadRequest extends FormRequest
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
            'min' => 'Tiene que tener al menos :min caracteres.',
            'integer' => 'Tiene que ser un número entero.',
            'boolean' => 'Tiene que ser sí o no.',
            'array' => 'Tiene que ser una lista.',
            'exists' => 'No existe o no está disponible.',
            'in' => 'El valor no es válido.',
            'enum' => 'El valor no es válido.',
            'confirmed' => 'La repetición no coincide.',
            'prohibited' => 'Este dato no se puede cambiar acá.',
        ];
    }

    /** Valor entero opcional que puede venir explícitamente en null (p. ej. sacar la jefatura). */
    protected function enteroONull(string $campo): ?int
    {
        return $this->input($campo) === null ? null : $this->integer($campo);
    }
}
