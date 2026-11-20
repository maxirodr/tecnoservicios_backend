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
    use FiltraPorObra;
    use RecortaBusqueda;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'obra_id' => self::reglaDeObra(),
            'estado' => ['nullable', Rule::in(self::estados())],
            'buscar' => ['nullable', 'string', 'max:'.BuscarRequest::LARGO_BUSCAR],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function estados(): array
    {
        return [PedidosQuery::ABIERTOS, PedidosQuery::TODOS, ...array_column(PedidoEstado::cases(), 'value')];
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
