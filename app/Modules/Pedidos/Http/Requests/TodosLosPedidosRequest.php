<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Application\Lecturas\FiltrosDePedidos;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Illuminate\Validation\Rule;

/**
 * Filtros y orden de `GET /api/pedidos` y `GET /api/pedidos/exportar` (RN-PED-43, 44): `obra_id` (id o
 * `sin_obra`), `sector_id`, `propietario_id` (id o `en_sector`), `solicitante_id`, `estado`, `prioridad`,
 * `vencidos=1`, `entrega_desde`/`entrega_hasta` (AAAA-MM-DD, inclusive), `orden`, `dir` y `page`.
 */
final class TodosLosPedidosRequest extends PedidosRequest
{
    use FiltraPorObra;

    private const FECHA = 'date_format:Y-m-d';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'obra_id' => self::reglaDeObra(),
            'sector_id' => ['nullable', 'integer', 'min:1'],
            'propietario_id' => ['nullable', self::reglaDeIdOValor(PedidosQuery::EN_SECTOR)],
            'solicitante_id' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::in(ListaDeInicioRequest::estados())],
            'prioridad' => ['nullable', Rule::enum(Prioridad::class)],
            'vencidos' => ['nullable', 'boolean'],
            'entrega_desde' => ['nullable', self::FECHA],
            'entrega_hasta' => ['nullable', self::FECHA, 'after_or_equal:entrega_desde'],
            'orden' => ['nullable', Rule::in([PedidosQuery::ORDEN_OBRA, PedidosQuery::ORDEN_PEDIDO, PedidosQuery::ORDEN_ENTREGA])],
            'dir' => ['nullable', Rule::in([PedidosQuery::ASC, PedidosQuery::DESC])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'entrega_desde.date_format' => 'Escribí la fecha como AAAA-MM-DD.',
            'entrega_hasta.date_format' => 'Escribí la fecha como AAAA-MM-DD.',
            'entrega_hasta.after_or_equal' => 'La entrega "hasta" no puede ser anterior a "desde".',
        ];
    }

    public function filtros(): FiltrosDePedidos
    {
        return new FiltrosDePedidos(
            obra: $this->obra(),
            sectorId: $this->enteroONull('sector_id'),
            propietario: $this->idOValor('propietario_id', PedidosQuery::EN_SECTOR),
            solicitanteId: $this->enteroONull('solicitante_id'),
            estado: $this->textoONull('estado') ?? PedidosQuery::ABIERTOS,
            prioridad: Prioridad::tryFrom((string) $this->textoONull('prioridad')),
            soloVencidos: $this->boolean('vencidos'),
            entregaDesde: $this->textoONull('entrega_desde'),
            entregaHasta: $this->textoONull('entrega_hasta'),
            orden: $this->textoONull('orden') ?? PedidosQuery::ORDEN_ENTREGA,
            direccion: $this->textoONull('dir') ?? PedidosQuery::ASC,
        );
    }

    public function pagina(): int
    {
        return max(1, $this->integer('page', 1));
    }
}
