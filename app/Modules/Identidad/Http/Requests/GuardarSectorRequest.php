<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Http\Rules\NombreDeSectorDisponible;

/** `POST /api/sectores` y `PATCH /api/sectores/{id}` (W-40). */
final class GuardarSectorRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('sector');

        return [
            'nombre' => ['required', 'string', 'max:80', new NombreDeSectorDisponible(is_numeric($id) ? (int) $id : null)],
        ];
    }

    public function nombre(): string
    {
        return $this->string('nombre')->squish()->toString();
    }
}
