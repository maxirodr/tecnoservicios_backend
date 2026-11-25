<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Estados;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Eventos\ArmaEvento;
use App\Modules\Pedidos\Application\Eventos\Avisados;
use App\Modules\Pedidos\Application\Pedidos\PedidoConAvisados;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Estados\AccionPedido;
use App\Modules\Pedidos\Domain\Events\FotoDelPedido;
use App\Modules\Pedidos\Domain\Events\Participantes;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Validation\ValidationException;

/**
 * F-32 / HU-035 (RN-PED-22…24): a una persona, el sector pasa a ser el suyo y el estado se mantiene; al
 * sector, queda sin persona y vuelve a No iniciada (resuelta DU-P04). El comentario opcional queda en el
 * hilo y viaja en el aviso de reasignación (sin aviso de comentario aparte). Solo pedidos abiertos.
 */
final readonly class ReasignarPedidoAction
{
    public const ACCION = 'reasignado';

    public function __construct(
        private Transiciones $transiciones,
        private AccesoPedido $acceso,
        private NuevoDuenio $nuevoDuenio,
        private ArmaEvento $evento,
        private Avisados $avisados,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, int $id, ?int $propietarioId, ?int $sectorId, ?string $comentario): PedidoConAvisados
    {
        $pedido = $this->transiciones->ejecutar($actor, $id, AccionPedido::Reasignar, function (Pedido $pedido) use ($actor, $propietarioId, $sectorId, $comentario): Pedido {
            BuscarPedido::exigir($this->acceso->reasignar($actor, $pedido), 'Solo el propietario o quien la pidió pueden reasignarla.');

            $anteriorId = $pedido->propietario_id;
            $estadoAntes = $pedido->estado;
            $cambios = ['propietario' => $this->nuevoDuenio->aplicar($pedido, $propietarioId, $sectorId)];
            $aSector = $pedido->enElSector();

            if ($aSector) {
                $pedido->estado = PedidoEstado::NoIniciada;
                $pedido->motivo_bloqueo = null;
            }

            if ($pedido->estado !== $estadoAntes) {
                $cambios['estado'] = Transiciones::estado($estadoAntes, $pedido->estado);
            }

            if ($comentario !== null) {
                $this->transiciones->comentario($pedido, $actor, $comentario);
                $cambios['comentario'] = ['antes' => null, 'despues' => $comentario];
            }

            return $this->transiciones->cerrar($pedido, self::ACCION, $cambios,
                static fn (int $actividad, Participantes $p, FotoDelPedido $f): PedidoReasignado => new PedidoReasignado(
                    $pedido->id, $actor->id, $actividad, $p, $f, $anteriorId, $aSector, $comentario,
                ));
        });

        return new PedidoConAvisados($pedido, $this->avisados->de($this->evento->participantes($pedido), $actor->id));
    }
}
