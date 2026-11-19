<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use App\Shared\Http\RecortaBusqueda;
use Illuminate\Validation\Rule;

/**
 * Filtros de las pestañas de Inicio: `obra_id` (una obra o `sin_obra`), `estado` (`abiertos` por
 * defecto · cada estado · `todos`), `buscar` (texto en la tarea) y `page`.
 */
final class ListaDeInicioRequest extends PedidosRequest
{
    use RecortaBusqueda;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'obra_id' => ['nullable', function (string $atributo, mixed $valor, \Closure $falla): void {
                if ($valor !== PedidosQuery::SIN_OBRA && ! (is_numeric($valor) && (int) $valor > 0)) {
                    $falla('El valor no es válido.');
                }
            }],
            'estado' => ['nullable', Rule::in([PedidosQuery::ABIERTOS, PedidosQuery::TODOS, ...array_column(PedidoEstado::cases(), 'value')])],
            'buscar' => ['nullable', 'string', 'max:'.BuscarRequest::LARGO_BUSCAR],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function obra(): int|string|null
    {
        $obra = $this->input('obra_id');

        return match (true) {
            $obra === PedidosQuery::SIN_OBRA => PedidosQuery::SIN_OBRA,
            is_numeric($obra) => (int) $obra,
            default => null,
        };
    }

    public function estado(): string
    {
        return $this->textoONull('estado') ?? PedidosQuery::ABIERTOS;
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }

    public function pagina(): int
    {
        return max(1, $this->integer('page', 1));
    }

    protected function prepareForValidation(): void
    {
        $this->recortarBusqueda('buscar', BuscarRequest::LARGO_BUSCAR);
    }
}
