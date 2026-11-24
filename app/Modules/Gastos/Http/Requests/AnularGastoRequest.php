<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

/** `POST /api/gastos/{id}/anular` (RN-GAS-40): con motivo obligatorio. */
final class AnularGastoRequest extends GastosRequest
{
    public const LARGO_MOTIVO = 2000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'max:'.self::LARGO_MOTIVO]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'motivo.required' => 'Contá por qué se anula.'];
    }

    public function motivo(): string
    {
        return $this->string('motivo')->trim()->toString();
    }
}
