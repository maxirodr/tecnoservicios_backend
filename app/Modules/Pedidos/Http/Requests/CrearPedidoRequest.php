<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Application\Pedidos\DatosPedido;
use Illuminate\Validation\Rule;

/**
 * `POST /api/pedidos` (PedidoAlta, pedidos.md §11.1, RN-PED-02). `obra_id` tiene que venir (null = Sin
 * obra). `solicitante_id` no se lee: el solicitante es siempre quien carga (HU-026 c1).
 */
final class CrearPedidoRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tarea' => ['required', 'string', 'max:'.self::LARGO_TAREA],
            'obra_id' => ['present', 'nullable', 'integer'],
            'sector_id' => ['required', 'integer'],
            'propietario_id' => ['required', 'integer'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d'],
            'fecha_pedido' => ['nullable', 'date_format:Y-m-d'],
            'periodo_inicio' => ['nullable', 'date_format:Y-m-d'],
            'periodo_fin' => ['nullable', 'date_format:Y-m-d'],
            'con_quien' => ['nullable', 'string', 'max:'.self::LARGO_CON_QUIEN],
            'aclaraciones' => ['nullable', 'string', 'max:'.self::LARGO_TEXTO],
            'seguidores' => ['nullable', 'array'],
            'seguidores.*' => ['integer', 'distinct'],
            'adjuntos' => ['nullable', 'array', 'max:'.self::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
            'prioridad' => ['nullable', Rule::enum(Prioridad::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'tarea.required' => 'Escribí la tarea.',
            'obra_id.present' => 'Elegí la obra o \'Sin obra\'.',
            'sector_id.required' => 'Elegí el sector.',
            'propietario_id.required' => 'Elegí el propietario.',
            'fecha_entrega.required' => 'Poné la fecha de entrega.',
        ];
    }

    public function datos(): DatosPedido
    {
        return new DatosPedido(
            $this->string('tarea')->trim()->toString(),
            $this->enteroONull('obra_id'),
            $this->integer('sector_id'),
            $this->integer('propietario_id'),
            $this->ids('seguidores'),
            $this->textoONull('con_quien'),
            $this->textoONull('fecha_pedido'),
            $this->string('fecha_entrega')->toString(),
            $this->textoONull('periodo_inicio'),
            $this->textoONull('periodo_fin'),
            $this->ids('adjuntos'),
            $this->textoONull('aclaraciones'),
            $this->filled('prioridad') ? Prioridad::from($this->string('prioridad')->toString()) : null,
        );
    }
}
