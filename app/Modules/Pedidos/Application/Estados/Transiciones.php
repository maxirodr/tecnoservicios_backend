<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Estados;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Eventos\ArmaEvento;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Estados\AccionPedido;
use App\Modules\Pedidos\Domain\Estados\PedidoStateMachine;
use App\Modules\Pedidos\Domain\Estados\TransicionNoPermitida;
use App\Modules\Pedidos\Domain\Events\EventoDePedido;
use App\Modules\Pedidos\Domain\Events\FotoDelPedido;
use App\Modules\Pedidos\Domain\Events\Participantes;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Models\PedidoComentario;
use App\Shared\Actividad\RegistroActividad;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Lo común a cada transición (RN-PED-15, RN-PED-59): dentro de una transacción, el pedido bloqueado para
 * escritura y visible (404), la tabla de `PedidoStateMachine` (409 con el estado actual), la actividad
 * en la misma transacción y el evento después del commit (`ShouldDispatchAfterCommit`).
 */
final readonly class Transiciones
{
    public function __construct(
        private Connection $db,
        private BuscarPedido $buscar,
        private PedidoStateMachine $maquina,
        private RegistroActividad $actividad,
        private ArmaEvento $evento,
        private Dispatcher $eventos,
    ) {}

    /**
     * @template T
     *
     * @param  Closure(Pedido, PedidoEstado): T  $paso  recibe el pedido bloqueado y el estado destino
     * @return T
     *
     * @throws HttpResponseException 404 · 409
     */
    public function ejecutar(User $actor, int $id, AccionPedido $accion, Closure $paso): mixed
    {
        return $this->db->transaction(function () use ($actor, $id, $accion, $paso): mixed {
            $pedido = $this->buscar->bloqueado($actor, $id);

            try {
                $destino = $this->maquina->destino($accion, $pedido->estado, $pedido->propietario?->nombre);
            } catch (TransicionNoPermitida $e) {
                throw BuscarPedido::conflicto($e);
            }

            return $paso($pedido, $destino);
        });
    }

    /**
     * Guarda el pedido, registra la acción y, si corresponde, dispara su evento con la actividad, los
     * participantes y la foto de después de la acción.
     *
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @param  (Closure(int, Participantes, FotoDelPedido): EventoDePedido)|null  $evento
     */
    public function cerrar(Pedido $pedido, string $accion, array $cambios, ?Closure $evento = null): Pedido
    {
        $pedido->save();
        $actividad = $this->actividad->registrar($pedido, $accion, $cambios);

        if ($evento !== null) {
            $this->eventos->dispatch($evento($actividad->id, $this->evento->participantes($pedido), $this->evento->foto($pedido)));
        }

        return $pedido;
    }

    /** Un comentario del hilo que nace de otra acción (reabrir, reasignar): no genera aviso propio. */
    public function comentario(Pedido $pedido, User $autor, string $texto): PedidoComentario
    {
        return PedidoComentario::query()->create(['pedido_id' => $pedido->id, 'user_id' => $autor->id, 'texto' => $texto]);
    }

    /**
     * @return array{antes: string, despues: string}
     */
    public static function estado(PedidoEstado $antes, PedidoEstado $despues): array
    {
        return ['antes' => $antes->value, 'despues' => $despues->value];
    }
}
