<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest de Pedidos: la forma en el borde, con los mensajes de la spec al pie de la letra.
 * La autorización va en las rutas (`permiso:pedidos,<nivel>`) y en los casos de uso (alcance, propietario,
 * quien lo pidió), no en authorize().
 */
abstract class PedidosRequest extends FormRequest
{
    public const MAX_ADJUNTOS = 10;

    public const LARGO_TAREA = 2000;

    public const LARGO_TEXTO = 5000;

    public const LARGO_CON_QUIEN = 150;

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
            'max.array' => 'Son demasiados (máximo :max).',
            'integer' => 'Tiene que ser un número entero.',
            'array' => 'Tiene que ser una lista.',
            'in' => 'El valor no es válido.',
            'enum' => 'El valor no es válido.',
            'boolean' => 'El valor no es válido.',
            'date_format' => 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.',
            'distinct' => 'Está repetido.',
        ];
    }

    protected function textoONull(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    protected function enteroONull(string $campo): ?int
    {
        $valor = $this->input($campo);

        return is_numeric($valor) ? (int) $valor : null;
    }

    /**
     * @return list<int>
     */
    protected function ids(string $campo): array
    {
        $valores = $this->input($campo);

        return is_array($valores)
            ? array_values(array_map(static fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $valores))
            : [];
    }
}
