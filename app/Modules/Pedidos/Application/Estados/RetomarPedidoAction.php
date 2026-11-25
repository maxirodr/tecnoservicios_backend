<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Estados;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Estados\AccionPedido;
use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * F-29 / HU-033 (RN-PED-18): de Bloqueada vuelve a En curso. El motivo deja de verse en la cabecera y
 * queda en el recorrido. No genera aviso (no está en la tabla del cap. 7).
 */
final readonly class RetomarPedidoAction
{
    public const ACCION = 'retomado';

    public function __construct(private Transiciones $transiciones, private AccesoPedido $acceso) {}

    public function handle(User $actor, int $id): Pedido
    {
        return $this->transiciones->ejecutar($actor, $id, AccionPedido::Retomar, function (Pedido $pedido, PedidoEstado $destino) use ($actor): Pedido {
            BuscarPedido::exigir($this->acceso->actuaComoPropietario($actor, $pedido), 'Solo el propietario puede retomarla.');

            $cambios = [
                'estado' => Transiciones::estado($pedido->estado, $destino),
                'motivo_bloqueo' => ['antes' => $pedido->motivo_bloqueo, 'despues' => null],
            ];
            $pedido->estado = $destino;
            $pedido->motivo_bloqueo = null;

            return $this->transiciones->cerrar($pedido, self::ACCION, $cambios);
        });
    }
}
