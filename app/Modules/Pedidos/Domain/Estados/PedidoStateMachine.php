<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Estados;

use App\Modules\Pedidos\Domain\Enums\PedidoEstado;

/**
 * Las transiciones del pedido en una sola tabla (pedidos.md §6.1, RN-PED-15). Reasignar no cambia el
 * estado acá: a una persona se mantiene y al sector vuelve a No iniciada (lo decide el caso de uso, DU-P04).
 * Cualquier otra combinación es `TransicionNoPermitida` (409 con el estado actual).
 */
final class PedidoStateMachine
{
    private const TABLA = [
        'tomar' => ['no_iniciada' => PedidoEstado::EnCurso],
        'bloquear' => ['no_iniciada' => PedidoEstado::Bloqueada, 'en_curso' => PedidoEstado::Bloqueada],
        'retomar' => ['bloqueada' => PedidoEstado::EnCurso],
        'completar' => ['no_iniciada' => PedidoEstado::Completada, 'en_curso' => PedidoEstado::Completada],
        'reabrir' => ['completada' => PedidoEstado::EnCurso],
        'reasignar' => [
            'no_iniciada' => PedidoEstado::NoIniciada,
            'en_curso' => PedidoEstado::EnCurso,
            'bloqueada' => PedidoEstado::Bloqueada,
        ],
    ];

    public function permite(AccionPedido $accion, PedidoEstado $actual): bool
    {
        return isset(self::TABLA[$accion->value][$actual->value]);
    }

    /**
     * @param  string|null  $propietario  nombre de quien lo tiene, para "Ya lo tomó Carla."
     *
     * @throws TransicionNoPermitida
     */
    public function destino(AccionPedido $accion, PedidoEstado $actual, ?string $propietario = null): PedidoEstado
    {
        return self::TABLA[$accion->value][$actual->value] ?? throw TransicionNoPermitida::para($accion, $actual, $propietario);
    }
}
