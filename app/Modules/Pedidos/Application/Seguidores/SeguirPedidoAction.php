<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Seguidores;

use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Acceso\Seguimiento;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * F-07 / HU-030 (RN-PED-26): cada uno se suma o se saca a sí mismo (resuelta DU-P11). Seguir uno que ya
 * sigo o dejar uno que no sigo: 200 sin cambios. Sumarse no avisa a nadie. Un pedido propio: 422.
 */
final readonly class SeguirPedidoAction
{
    public function __construct(
        private Connection $db,
        private BuscarPedido $buscar,
        private AccesoPedido $acceso,
    ) {}

    /**
     * @throws HttpResponseException|ValidationException
     */
    public function handle(User $actor, int $id): bool
    {
        $pedido = $this->buscar->visible($actor, $id);

        if ($pedido->esSolicitante($actor) || $pedido->esPropietario($actor)) {
            throw ValidationException::withMessages(['pedido' => ['Ya recibís los avisos de este pedido.']]);
        }

        BuscarPedido::exigir($this->acceso->puedeNivel($actor, Nivel::Ver));

        $this->db->table(Seguimiento::TABLA)->insertOrIgnore([
            'pedido_id' => $pedido->id,
            'user_id' => $actor->id,
            'created_at' => Carbon::now(),
        ]);

        return true;
    }
}
