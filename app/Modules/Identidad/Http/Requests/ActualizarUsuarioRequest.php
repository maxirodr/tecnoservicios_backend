<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Http\Rules\DominioPermitido;
use App\Modules\Identidad\Http\Rules\MailDisponible;
use Illuminate\Validation\Rule;

/** `PATCH /api/usuarios/{id}` (W-39): solo los campos que vienen. */
final class ActualizarUsuarioRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var list<string> $dominios */
        $dominios = config('identidad.dominios_permitidos', []);

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:190', new MailDisponible($this->usuarioId()), new DominioPermitido($dominios)],
            'sector_id' => ['sometimes', 'required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'jefe_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'activo' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array{nombre?: string, email?: string, sector_id?: int, jefe_id?: int|null, telefono?: string|null, activo?: bool}
     */
    public function datos(): array
    {
        $datos = [];

        foreach (['nombre', 'email'] as $campo) {
            if ($this->has($campo)) {
                $datos[$campo] = $this->string($campo)->toString();
            }
        }

        if ($this->has('sector_id')) {
            $datos['sector_id'] = $this->integer('sector_id');
        }

        if ($this->has('jefe_id')) {
            $datos['jefe_id'] = $this->enteroONull('jefe_id');
        }

        if ($this->has('telefono')) {
            $datos['telefono'] = $this->filled('telefono') ? $this->string('telefono')->toString() : null;
        }

        if ($this->has('activo')) {
            $datos['activo'] = $this->boolean('activo');
        }

        return $datos;
    }

    private function usuarioId(): int
    {
        $id = $this->route('usuario');

        return is_numeric($id) ? (int) $id : 0;
    }
}
