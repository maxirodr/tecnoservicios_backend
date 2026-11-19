<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Estados;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Estados\AccionPedido;
use App\Modules\Pedidos\Domain\Events\FotoDelPedido;
use App\Modules\Pedidos\Domain\Events\Participantes;
use App\Modules\Pedidos\Domain\Events\PedidoBloqueado;
use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * F-29 / HU-033 (RN-PED-17): el propietario lo bloquea con motivo, desde No iniciada o En curso (resuelta
 * DU-P03). En el sector, la persona del sector que lo bloquea queda propietaria (DU-P02).
 */
final readonly class BloquearPedidoAction
{
    public const ACCION = 'bloqueado';

    public function __construct(private Transiciones $transiciones, private AccesoPedido $acceso) {}

    public function handle(User $actor, int $id, string $motivo): Pedido
    {
        return $this->transiciones->ejecutar($actor, $id, AccionPedido::Bloquear, function (Pedido $pedido, PedidoEstado $destino) use ($actor, $motivo): Pedido {
            BuscarPedido::exigir($this->acceso->actuaComoPropietario($actor, $pedido), 'Solo el propietario puede bloquearlo.');

            $cambios = ['estado' => Transiciones::estado($pedido->estado, $destino), 'motivo_bloqueo' => ['antes' => null, 'despues' => $motivo]];

            if ($pedido->enElSector()) {
                $pedido->propietario_id = $actor->id;
                $cambios['propietario'] = ['antes' => null, 'despues' => $actor->nombre];
            }

            $pedido->estado = $destino;
            $pedido->motivo_bloqueo = $motivo;

            return $this->transiciones->cerrar($pedido, self::ACCION, $cambios,
                static fn (int $actividad, Participantes $p, FotoDelPedido $f): PedidoBloqueado => new PedidoBloqueado($pedido->id, $actor->id, $actividad, $p, $f, $motivo));
        });
    }
}
