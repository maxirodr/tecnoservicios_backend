<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Application\Usuarios\ListarUsuariosQuery;
use Illuminate\Validation\Rule;

/** `GET /api/usuarios?buscar=&sector_id=&activo=true|false|todos&page=` (W-15, RN-IDE-21). */
final class ListarUsuariosRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:190'],
            'sector_id' => ['nullable', 'integer'],
            'activo' => ['nullable', Rule::in([ListarUsuariosQuery::ACTIVOS, ListarUsuariosQuery::INACTIVOS, ListarUsuariosQuery::TODOS])],
        ];
    }

    public function buscar(): ?string
    {
        return $this->filled('buscar') ? $this->string('buscar')->toString() : null;
    }

    public function sectorId(): ?int
    {
        return $this->filled('sector_id') ? $this->integer('sector_id') : null;
    }

    public function activo(): string
    {
        return $this->filled('activo') ? $this->string('activo')->toString() : ListarUsuariosQuery::ACTIVOS;
    }
}
