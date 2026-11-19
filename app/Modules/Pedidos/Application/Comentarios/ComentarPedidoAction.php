<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Comentarios;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Eventos\ArmaEvento;
use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Models\PedidoComentario;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * F-30 / HU-029 (RN-PED-25): comenta cualquiera que vea el pedido (con Ver alcanza, o lo propio), en
 * cualquier estado. Guarda autor, fecha y hora; queda "comentó" en el recorrido y dispara `PedidoComentado`
 * (Avisos decide a quién, nunca al autor).
 */
final readonly class ComentarPedidoAction
{
    public const ACCION = 'comentado';

    public function __construct(
        private Connection $db,
        private BuscarPedido $buscar,
        private RegistroActividad $actividad,
        private ArmaEvento $evento,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws HttpResponseException 404 si no lo ve
     */
    public function handle(User $actor, int $id, string $texto): PedidoComentario
    {
        $pedido = $this->buscar->visible($actor, $id);

        return $this->db->transaction(function () use ($actor, $pedido, $texto): PedidoComentario {
            $comentario = PedidoComentario::query()->create(['pedido_id' => $pedido->id, 'user_id' => $actor->id, 'texto' => $texto]);
            $actividad = $this->actividad->registrar($pedido, self::ACCION, ['comentario' => ['antes' => null, 'despues' => $texto]]);

            $this->eventos->dispatch(new PedidoComentado(
                $pedido->id, $actor->id, $actividad->id, $this->evento->participantes($pedido), $this->evento->foto($pedido), $comentario->id, $texto,
            ));

            return $comentario->load('autor');
        });
    }
}
