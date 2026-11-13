<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Validation\Rule;

/** `POST /api/obras/{id}/estado` (W-22): `{estado, confirmo_pedidos_abiertos: [ids]}`. */
final class CambiarEstadoRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(ObraEstado::class)],
            'confirmo_pedidos_abiertos' => ['nullable', 'array'],
            'confirmo_pedidos_abiertos.*' => ['integer'],
        ];
    }

    public function estado(): ObraEstado
    {
        return ObraEstado::from($this->string('estado')->toString());
    }

    /**
     * @return list<int>
     */
    public function pedidosConfirmados(): array
    {
        $ids = $this->input('confirmo_pedidos_abiertos', []);

        return is_array($ids) ? array_values(array_map(intval(...), $ids)) : [];
    }
}
