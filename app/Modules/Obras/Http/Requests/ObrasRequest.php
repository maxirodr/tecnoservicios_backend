<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Domain\Valores\Revision;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest de Obras: valida la forma en el borde con mensajes en las palabras del
 * cliente. La autorización va en las rutas (`permiso:<funcion>,<nivel>`) y en los casos de uso
 * (alcance "solo sus obras", responsable), no en authorize().
 */
abstract class ObrasRequest extends FormRequest
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
            'email' => 'El mail no es válido.',
            'max' => 'Es demasiado largo (máximo :max caracteres).',
            'max.array' => 'Son demasiados (máximo :max).',
            'min.array' => 'Agregá al menos :min.',
            'integer' => 'Tiene que ser un número entero.',
            'array' => 'Tiene que ser una lista.',
            'exists' => 'No existe o no está disponible.',
            'in' => 'El valor no es válido.',
            'enum' => 'El valor no es válido.',
            // PU-209: las fechas se escriben siempre dd/mm/aaaa (la API recibe AAAA-MM-DD del front).
            'date_format' => 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.',
            'after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'prohibited' => 'Este dato no se puede cambiar acá.',
            'distinct' => 'Está repetido.',
            // PU-033: una revisión larga tampoco es REV01; el mensaje dice cómo escribirla (RN-OBR-14).
            'revision.max' => Revision::MENSAJE,
            'presupuesto.revision.max' => Revision::MENSAJE,
            'adicionales.*.revision.max' => Revision::MENSAJE,
            'presupuesto_principal.revision.max' => Revision::MENSAJE,
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

        return $valor === null || $valor === '' ? null : (int) $valor;
    }
}
