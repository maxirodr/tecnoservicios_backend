<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Pedidos\CalculoDePrioridad;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Prioridad\Anticipacion;
use Illuminate\Http\Exceptions\HttpResponseException;

/** `GET /api/pedidos/{id}` (W-09, HU-029): el pedido visible, con comentarios, adjuntos, seguidores y botones. */
final readonly class DetallePedidoQuery
{
    public const CON = [
        'obra.principal', 'sector', 'propietario.sector', 'solicitante.sector', 'completadoPor',
        'comentarios.autor', 'seguidores', 'adjuntos.subidoPor', 'comprobantes.subidoPor',
    ];

    public function __construct(
        private BuscarPedido $buscar,
        private AccesoPedido $acceso,
        private CalculoDePrioridad $prioridad,
    ) {}

    /**
     * @param  list<array{id: int, nombre: string}>|null  $avisados
     * @param  bool  $recienActuo  la respuesta de una acción que ya se autorizó: si al reasignar el pedido
     *                             quedó fuera de su alcance, igual ve cómo quedó (sin botones)
     *
     * @throws HttpResponseException 404 fuera de alcance
     */
    public function handle(User $user, int $id, ?array $avisados = null, bool $recienActuo = false): DetallePedido
    {
        $pedido = $recienActuo
            ? Pedido::query()->with(self::CON)->findOrFail($id)
            : $this->buscar->visible($user, $id, self::CON);
        $sigo = $pedido->seguidores->contains('id', $user->id);

        return new DetallePedido(
            $pedido,
            $sigo,
            $this->acceso->resumen($user, $pedido, $sigo),
            Anticipacion::dias($pedido->fecha_pedido, $pedido->fecha_entrega),
            $this->prioridad->calculada($pedido->fecha_pedido, $pedido->fecha_entrega),
            $avisados,
        );
    }
}
