<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Application\Recorrido\TextoDeActividad;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\AdjuntosService;

/**
 * "Último movimiento" de Pedidos que pedí (RN-PED-39): la última línea del recorrido de cada pedido de la
 * página, en una sola consulta. Los adjuntos sueltos no cuentan como movimiento (acompañan otra acción).
 */
final class UltimosMovimientos
{
    /**
     * @param  iterable<Pedido>  $pedidos  con `comprobantes_count`
     * @return array<int, array{cuando: string|null, texto: string}>
     */
    public function de(iterable $pedidos): array
    {
        $comprobantes = [];

        foreach ($pedidos as $pedido) {
            $cantidad = $pedido->getAttribute('comprobantes_count');
            $comprobantes[$pedido->id] = is_numeric($cantidad) ? (int) $cantidad : 0;
        }

        if ($comprobantes === []) {
            return [];
        }

        $ultimas = Actividad::query()
            ->with('user')
            ->whereIn('id', Actividad::query()
                ->selectRaw('MAX(id)')
                ->where('sujeto_type', Pedido::MORPH)
                ->whereIn('sujeto_id', array_keys($comprobantes))
                ->whereNotIn('accion', [AdjuntosService::ACCION_AGREGADO, AdjuntosService::ACCION_QUITADO])
                ->groupBy('sujeto_id'))
            ->get();

        $movimientos = [];

        foreach ($ultimas as $actividad) {
            $movimientos[$actividad->sujeto_id] = [
                'cuando' => FechaEmpresa::instante($actividad->created_at),
                'texto' => TextoDeActividad::ultimoMovimiento($actividad, $comprobantes[$actividad->sujeto_id] ?? 0),
            ];
        }

        return $movimientos;
    }
}
