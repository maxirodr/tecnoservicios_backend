<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use App\Modules\Rendicion\Infrastructure\MaterialesDeObraEloquent;

/** `GET /api/obras/{id}/materiales/opciones?buscar=` (W-10, W-46; rendicion.md §12.2). */
final class OpcionesDeMaterialesRequest extends RendicionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['sometimes', 'nullable', 'string', 'max:'.MaterialesDeObraEloquent::NOMBRE_MAXIMO]];
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }
}
