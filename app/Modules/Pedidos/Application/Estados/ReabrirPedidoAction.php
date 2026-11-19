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
use App\Modules\Pedidos\Domain\Events\PedidoReabierto;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Validation\ValidationException;

/**
 * F-65 / HU-036 (RN-PED-20, RN-PED-60): quien lo pidió lo reabre con comentario; vuelve a En curso con el
 * mismo propietario, el comentario queda en el hilo (viaja en el aviso de reapertura) y se limpia el
 * cierre (queda en el recorrido; los comprobantes se conservan). Con el propietario inactivo hay que
 * elegir en el mismo paso a quién pasárselo (DU-P09); si se elige un sector, queda No iniciada para que
 * lo tome el primero que pueda (DU-16).
 */
final readonly class ReabrirPedidoAction
{
    public const ACCION = 'reabierto';

    public function __construct(
        private Transiciones $transiciones,
        private AccesoPedido $acceso,
        private NuevoDuenio $nuevoDuenio,
    ) {}

    public function handle(User $actor, int $id, string $comentario, ?int $propietarioId, ?int $sectorId): Pedido
    {
        return $this->transiciones->ejecutar($actor, $id, AccionPedido::Reabrir, function (Pedido $pedido, PedidoEstado $destino) use ($actor, $comentario, $propietarioId, $sectorId): Pedido {
            BuscarPedido::exigir($this->acceso->reabrir($actor, $pedido), 'Solo quien lo pidió puede reabrirlo.');

            $cambios = ['comentario_cierre' => ['antes' => $pedido->comentario_cierre, 'despues' => null]];
            $inactivo = $pedido->propietario !== null && ! $pedido->propietario->activo;

            if ($propietarioId !== null || $sectorId !== null) {
                $cambios['propietario'] = $this->nuevoDuenio->aplicar($pedido, $propietarioId, $sectorId);
            } elseif ($inactivo) {
                $nombre = $pedido->propietario->nombre ?? '';

                throw ValidationException::withMessages(['propietario_id' => ["{$nombre} está inactiva: elegí a quién se lo pasás."]]);
            }

            $destino = $pedido->enElSector() ? PedidoEstado::NoIniciada : $destino;
            $cambios = ['estado' => Transiciones::estado($pedido->estado, $destino), ...$cambios, 'comentario' => ['antes' => null, 'despues' => $comentario]];
            $pedido->estado = $destino;
            $pedido->comentario_cierre = null;
            $pedido->completado_at = null;
            $pedido->completado_por = null;
            $this->transiciones->comentario($pedido, $actor, $comentario);

            return $this->transiciones->cerrar($pedido, self::ACCION, $cambios,
                static fn (int $actividad, Participantes $p, FotoDelPedido $f): PedidoReabierto => new PedidoReabierto($pedido->id, $actor->id, $actividad, $p, $f, $comentario));
        });
    }
}
