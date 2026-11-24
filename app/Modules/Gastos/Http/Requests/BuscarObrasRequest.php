<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

/** `GET /api/gastos/obras-opciones?buscar=` (selector de obra del renglón, RN-GAS-13). */
final class BuscarObrasRequest extends GastosRequest
{
    public const LARGO_BUSQUEDA = 100;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:'.self::LARGO_BUSQUEDA]];
    }

    public function buscar(): ?string
    {
        return self::texto($this->input('buscar'));
    }
}
