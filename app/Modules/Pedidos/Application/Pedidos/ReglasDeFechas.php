<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Pedidos\Application\FechaEmpresa;
use Illuminate\Validation\ValidationException;

/**
 * Combinaciones de fechas del pedido (resuelta DU-P08, RN-PED-04 y RN-PED-05), iguales al crear y al
 * editar: la fecha de pedido no es posterior a hoy (Argentina); el período va completo y el fin no es
 * anterior al inicio. La entrega puede ser hoy, pasada o anterior al pedido (queda Urgente).
 */
final class ReglasDeFechas
{
    /**
     * @throws ValidationException
     */
    public static function validar(?string $fechaPedido, ?string $periodoInicio, ?string $periodoFin): void
    {
        $errores = [];

        if ($fechaPedido !== null && $fechaPedido > FechaEmpresa::hoyTexto()) {
            $errores['fecha_pedido'] = ['La fecha en que se pidió no puede ser posterior a hoy.'];
        }

        if (($periodoInicio === null) !== ($periodoFin === null)) {
            $errores[$periodoInicio === null ? 'periodo_inicio' : 'periodo_fin'] = ['Completá inicio y fin del período.'];
        } elseif ($periodoInicio !== null && $periodoFin !== null && $periodoFin < $periodoInicio) {
            $errores['periodo_fin'] = ['El fin del período no puede ser anterior al inicio.'];
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}
