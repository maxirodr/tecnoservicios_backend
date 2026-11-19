<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * Cómo queda la prioridad al editar (RN-PED-12, RN-PED-13, resuelta DU-P07): "Usar la calculada" la
 * vuelve automática; elegir una a mano la marca manual salvo que coincida con la calculada; si solo
 * cambian las fechas, la estrategia del pedido decide (la manual se mantiene, la automática se recalcula
 * con los plazos vigentes). Deja el pedido con los valores nuevos, sin guardar.
 */
final readonly class PrioridadAlEditar
{
    public function __construct(private CalculoDePrioridad $calculo) {}

    public function aplicar(Pedido $pedido, CambiosPedido $cambios, bool $cambiaronFechas): void
    {
        $calculada = $this->calculo->calculada($pedido->fecha_pedido, $pedido->fecha_entrega);

        if ($cambios->usarCalculada) {
            $pedido->prioridad_manual = false;
            $pedido->prioridad = $calculada ?? $pedido->prioridad;

            return;
        }

        if ($cambios->prioridad !== null) {
            $pedido->prioridad_manual = $cambios->prioridad !== $calculada;
            $pedido->prioridad = $cambios->prioridad;

            return;
        }

        if ($cambiaronFechas) {
            $pedido->prioridad = $this->calculo->estrategia($pedido->prioridad_manual)
                ->prioridad($pedido->fecha_pedido, $pedido->fecha_entrega, $pedido->prioridad);
        }
    }
}
