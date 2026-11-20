<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Illuminate\Validation\Rule;

/** `?estado=` de Tareas de mi gente: `abiertos` (por defecto) · cada estado · `todos`. */
final class EstadoDeListaRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['estado' => ['nullable', Rule::in(ListaDeInicioRequest::estados())]];
    }

    public function estado(): string
    {
        return $this->textoONull('estado') ?? PedidosQuery::ABIERTOS;
    }
}
