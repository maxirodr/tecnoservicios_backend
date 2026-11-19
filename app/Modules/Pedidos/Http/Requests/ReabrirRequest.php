<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/**
 * `POST /api/pedidos/{id}/reabrir` `{comentario, propietario_id?}` o `{comentario, sector_id?}`
 * (obligatorio uno de los dos si el propietario está inactivo, DU-P09).
 */
final class ReabrirRequest extends TextoObligatorioRequest
{
    protected function campo(): string
    {
        return 'comentario';
    }

    protected function mensajeVacio(): string
    {
        return 'Contá qué falta.';
    }

    protected function otrasReglas(): array
    {
        return [
            'propietario_id' => ['nullable', 'integer'],
            'sector_id' => ['nullable', 'integer'],
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
}
