<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Seguidores;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Acceso\Seguimiento;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/** F-07 / HU-030 c4: "dejar de seguir" sale de Tareas que sigo; dejar uno que no sigo responde igual. */
final readonly class DejarDeSeguirPedidoAction
{
    public function __construct(
        private Connection $db,
        private BuscarPedido $buscar,
    ) {}

    /**
     * @throws HttpResponseException 404 si no lo ve
     */
    public function handle(User $actor, int $id): bool
    {
        $pedido = $this->buscar->visible($actor, $id);
        $this->db->table(Seguimiento::TABLA)->where('pedido_id', $pedido->id)->where('user_id', $actor->id)->delete();

        return false;
    }
}
