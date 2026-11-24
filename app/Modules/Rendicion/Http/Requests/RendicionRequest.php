<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest de Rendicion: la forma en el borde, con mensajes en las palabras del cliente. La
 * autorización (matriz y alcance "solo sus obras") va en las rutas y en `AccesoRendicion`, no en authorize().
 */
abstract class RendicionRequest extends FormRequest
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
            'max' => 'Es demasiado largo (máximo :max caracteres).',
            'integer' => 'Tiene que ser un número entero.',
            'in' => 'El valor no es válido.',
            'date_format' => 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.',
        ];
    }

    protected function textoONull(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }
}
