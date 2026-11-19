<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Http\Requests;

use App\Modules\Avisos\Domain\TipoAviso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** `GET /api/avisos?leidos=todos|no|si&tipo=&por_pagina=5|25&page=` (avisos.md §9). Sin filtros: todos, de a 25. */
final class ListarAvisosRequest extends FormRequest
{
    public const POR_PAGINA_CAMPANA = 5;

    public const POR_PAGINA_LISTA = 25;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'leidos' => ['nullable', Rule::in(['todos', 'no', 'si'])],
            'tipo' => ['nullable', Rule::enum(TipoAviso::class)],
            'por_pagina' => ['nullable', 'integer', Rule::in([self::POR_PAGINA_CAMPANA, self::POR_PAGINA_LISTA])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'in' => 'El valor no es válido.',
            'enum' => 'Ese tipo de aviso no existe.',
            'integer' => 'Tiene que ser un número entero.',
            'min' => 'Tiene que ser 1 o más.',
        ];
    }

    /** null = todos. */
    public function leidos(): ?bool
    {
        return match ($this->input('leidos')) {
            'si' => true,
            'no' => false,
            default => null,
        };
    }

    public function tipo(): ?TipoAviso
    {
        return TipoAviso::tryFrom($this->string('tipo')->toString());
    }

    public function porPagina(): int
    {
        return $this->integer('por_pagina', self::POR_PAGINA_LISTA);
    }

    public function pagina(): int
    {
        return max(1, $this->integer('page', 1));
    }
}
