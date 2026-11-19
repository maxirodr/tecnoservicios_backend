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
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * F-31 / HU-034 (RN-PED-19): el propietario lo completa desde No iniciada o En curso (resuelta DU-15),
 * con comentario y comprobantes opcionales (archivo o link, contexto `comprobante_cierre`). Guarda quién
 * y cuándo. Desde Bloqueada: 409 "Primero retomalo: está bloqueado.". En el sector, queda propietaria
 * la persona que lo completa (DU-P02).
 */
final readonly class CompletarPedidoAction
{
    public const ACCION = 'completado';

    public function __construct(
        private Transiciones $transiciones,
        private AccesoPedido $acceso,
        private AdjuntosService $adjuntos,
    ) {}

    /**
     * @param  list<int>  $comprobantes  ids temporales subidos por quien completa
     */
    public function handle(User $actor, int $id, string $comentario, array $comprobantes): Pedido
    {
        return $this->transiciones->ejecutar($actor, $id, AccionPedido::Completar, function (Pedido $pedido, PedidoEstado $destino) use ($actor, $comentario, $comprobantes): Pedido {
            BuscarPedido::exigir($this->acceso->actuaComoPropietario($actor, $pedido), 'Solo el propietario puede completarlo.');

            $cambios = [
                'estado' => Transiciones::estado($pedido->estado, $destino),
                'comentario_cierre' => ['antes' => null, 'despues' => $comentario],
            ];

            if ($pedido->enElSector()) {
                $pedido->propietario_id = $actor->id;
                $cambios['propietario'] = ['antes' => null, 'despues' => $actor->nombre];
            }

            $pedido->estado = $destino;
            $pedido->comentario_cierre = $comentario;
            $pedido->completado_at = Carbon::now();
            $pedido->completado_por = $actor->id;
            $cantidad = count(array_unique($comprobantes));

            $this->transiciones->cerrar($pedido, self::ACCION, $cambios,
                static fn (int $actividad, Participantes $p, FotoDelPedido $f): PedidoCompletado => new PedidoCompletado($pedido->id, $actor->id, $actividad, $p, $f, $comentario, $cantidad));
            try {
                $this->adjuntos->asociar($actor->id, $comprobantes, $pedido, Pedido::CONTEXTO_COMPROBANTES);
            } catch (ValidationException) {
                throw ValidationException::withMessages(['comprobantes' => [AdjuntosService::MENSAJE_NO_UTILIZABLE]]);
            }

            return $pedido;
        });
    }
}
