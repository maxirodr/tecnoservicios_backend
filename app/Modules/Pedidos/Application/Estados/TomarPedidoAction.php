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
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use App\Modules\Pedidos\Domain\Models\Pedido;

/**
 * F-28 / HU-032 (RN-PED-16): el propietario lo toma y pasa a En curso. En el sector lo toma cualquier
 * persona activa del sector y queda propietaria; si dos lo toman a la vez, la segunda recibe 409
 * "Ya lo tomó Carla." (la fila está bloqueada mientras la primera lo toma).
 */
final readonly class TomarPedidoAction
{
    public const ACCION = 'tomado';

    public function __construct(private Transiciones $transiciones, private AccesoPedido $acceso) {}

    public function handle(User $actor, int $id): Pedido
    {
        return $this->transiciones->ejecutar($actor, $id, AccionPedido::Tomar, function (Pedido $pedido, PedidoEstado $destino) use ($actor): Pedido {
            BuscarPedido::exigir($this->acceso->actuaComoPropietario($actor, $pedido), 'Solo el propietario puede tomarlo.');

            $desdeSector = $pedido->enElSector();
            $cambios = ['estado' => Transiciones::estado($pedido->estado, $destino)];

            if ($desdeSector) {
                $pedido->propietario_id = $actor->id;
                $cambios['propietario'] = ['antes' => null, 'despues' => $actor->nombre];
            }

            $pedido->estado = $destino;

            return $this->transiciones->cerrar($pedido, self::ACCION, $cambios,
                static fn (int $actividad, Participantes $p, FotoDelPedido $f): PedidoTomado => new PedidoTomado($pedido->id, $actor->id, $actividad, $p, $f, $desdeSector));
        });
    }
}
