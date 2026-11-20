<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Domain\Enums\EquipoEstado;
use Illuminate\Validation\Rule;

/** `GET /api/equipos-reparacion?estado=&page=` (§11.2). */
final class ListarEquiposRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::enum(EquipoEstado::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function estado(): ?EquipoEstado
    {
        return EquipoEstado::tryFrom((string) $this->textoONull('estado'));
    }

    public function pagina(): int
    {
        return max(1, $this->integer('page', 1));
    }
}
