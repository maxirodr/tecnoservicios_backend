<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;

/**
 * `PATCH /api/me` (W-19, RN-IDE-16): solo nombre y teléfono. Mail, sector, jefatura y activo
 * los cambia gerencia: si vienen, 422 y no cambia nada (HU-003 criterio 3). Con `updated_at`, 409
 * si gerencia la cambió desde que abrió el formulario (PU-108).
 */
final class ActualizarPerfilRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['prohibited'],
            'sector_id' => ['prohibited'],
            'jefe_id' => ['prohibited'],
            'activo' => ['prohibited'],
            ...VersionDeFila::reglas(),
        ];
    }

    /** `updated_at` que tenía la persona al abrir Mi perfil (PU-108). */
    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }

    /**
     * @return array{nombre?: string, telefono?: string|null}
     */
    public function datos(): array
    {
        $datos = [];

        if ($this->has('nombre')) {
            $datos['nombre'] = $this->string('nombre')->toString();
        }

        if ($this->has('telefono')) {
            $datos['telefono'] = $this->input('telefono') === null ? null : $this->string('telefono')->toString();
        }

        return $datos;
    }
}
