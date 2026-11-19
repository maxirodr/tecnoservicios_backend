<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/** `POST /api/pedidos/{id}/reasignar` `{propietario_id}` o `{sector_id}`, `comentario?` (W-31, RN-PED-23). */
final class ReasignarRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'propietario_id' => ['nullable', 'integer'],
            'sector_id' => ['nullable', 'integer'],
            'comentario' => ['nullable', 'string', 'max:'.self::LARGO_TEXTO],
        ];
    }

    public function propietarioId(): ?int
    {
        return $this->enteroONull('propietario_id');
    }

    public function sectorId(): ?int
    {
        return $this->enteroONull('sector_id');
    }

    public function comentario(): ?string
    {
        return $this->textoONull('comentario');
    }
}
