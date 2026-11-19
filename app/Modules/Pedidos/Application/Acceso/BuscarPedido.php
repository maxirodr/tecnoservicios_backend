<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Estados\TransicionNoPermitida;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Trae el pedido solo si la persona lo puede ver (IDOR): uno fuera de alcance da 404 "Pedido no
 * encontrado.", igual que uno que no existe (RN-PED-33). También arma los 403 y 409 del módulo.
 */
final readonly class BuscarPedido
{
    public const NO_ENCONTRADO = 'Pedido no encontrado.';

    public function __construct(private AccesoPedido $acceso) {}

    /**
     * @param  list<string>  $with
     *
     * @throws HttpResponseException
     */
    public function visible(User $user, int $id, array $with = []): Pedido
    {
        return $this->exigirVisible($user, Pedido::query()->with($with)->find($id));
    }

    /**
     * Dentro de la transacción: bloquea la fila para escritura (RN-PED-59), así dos personas no lo toman
     * ni lo completan a la vez; la segunda ve el estado que dejó la primera.
     *
     * @throws HttpResponseException
     */
    public function bloqueado(User $user, int $id): Pedido
    {
        return $this->exigirVisible($user, Pedido::query()->with('propietario')->lockForUpdate()->find($id));
    }

    /**
     * @throws HttpResponseException 403 con el motivo (reglas fijas de §7.2)
     */
    public static function exigir(bool $permitido, string $mensaje = 'No tenés permiso para hacer esto.'): void
    {
        if (! $permitido) {
            throw new HttpResponseException(ApiResponse::error($mensaje, 403));
        }
    }

    /** 409 con el estado actual (RN-PED-15): el front refresca el pedido sin repetir la acción. */
    public static function conflicto(TransicionNoPermitida $e): HttpResponseException
    {
        return new HttpResponseException(new JsonResponse(
            ApiResponse::body(['estado' => $e->actual->value], $e->getMessage(), null),
            409,
        ));
    }

    /**
     * @throws HttpResponseException
     */
    private function exigirVisible(User $user, ?Pedido $pedido): Pedido
    {
        if (! $pedido instanceof Pedido || ! $this->acceso->ver($user, $pedido)) {
            throw new HttpResponseException(ApiResponse::error(self::NO_ENCONTRADO, 404));
        }

        return $pedido;
    }
}
