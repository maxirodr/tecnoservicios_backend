<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/** `?sector_id=` de Carga por persona ("Sector: todos ▾"). */
final class CargaPorPersonaRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['sector_id' => ['nullable', 'integer', 'min:1']];
    }

    public function sectorId(): ?int
    {
        return $this->enteroONull('sector_id');
    }
}
