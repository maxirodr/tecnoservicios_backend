<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Application\FechaEmpresa;
use Carbon\CarbonImmutable;

/** `GET /api/pedidos/prioridad-sugerida?fecha_pedido=&fecha_entrega=` (sin fecha de pedido: hoy). */
final class PrioridadSugeridaRequest extends PedidosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha_pedido' => ['nullable', 'date_format:Y-m-d'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'fecha_entrega.required' => 'Poné la fecha de entrega.'];
    }

    public function fechaPedido(): CarbonImmutable
    {
        $fecha = $this->textoONull('fecha_pedido');

        return $fecha === null ? FechaEmpresa::hoy() : CarbonImmutable::parse($fecha);
    }

    public function fechaEntrega(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('fecha_entrega')->toString());
    }
}
