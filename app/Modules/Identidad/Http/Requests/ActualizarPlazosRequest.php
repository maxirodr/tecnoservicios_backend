<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

/** `PUT /api/plazos-prioridad` (W-50, identidad.md §4.4): 0 ≤ urgente < prioritario ≤ 365. */
final class ActualizarPlazosRequest extends IdentidadRequest
{
    private const MAXIMO_DIAS = 365;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'urgente_hasta_dias' => ['required', 'integer', 'min:0', 'max:'.self::MAXIMO_DIAS],
            'prioritario_hasta_dias' => ['required', 'integer', 'max:'.self::MAXIMO_DIAS, 'gt:urgente_hasta_dias'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'min' => 'No puede ser negativo.',
            'max' => 'No puede ser más de :max días.',
            'prioritario_hasta_dias.gt' => 'Prioritario tiene que ser mayor que Urgente.',
        ];
    }

    public function urgente(): int
    {
        return $this->integer('urgente_hasta_dias');
    }

    public function prioritario(): int
    {
        return $this->integer('prioritario_hasta_dias');
    }
}
