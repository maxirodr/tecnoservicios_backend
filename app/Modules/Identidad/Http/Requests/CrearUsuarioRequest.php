<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Http\Rules\DominioPermitido;
use App\Modules\Identidad\Http\Rules\MailDisponible;
use Illuminate\Validation\Rule;

/** `POST /api/usuarios` (W-39, identidad.md §4.1). */
final class CrearUsuarioRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var list<string> $dominios */
        $dominios = config('identidad.dominios_permitidos', []);

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', new MailDisponible, new DominioPermitido($dominios)],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'jefe_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'telefono' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array{nombre: string, email: string, sector_id: int, jefe_id: int|null, telefono: string|null}
     */
    public function datos(): array
    {
        return [
            'nombre' => $this->string('nombre')->toString(),
            'email' => $this->string('email')->toString(),
            'sector_id' => $this->integer('sector_id'),
            'jefe_id' => $this->enteroONull('jefe_id'),
            'telefono' => $this->filled('telefono') ? $this->string('telefono')->toString() : null,
        ];
    }
}
