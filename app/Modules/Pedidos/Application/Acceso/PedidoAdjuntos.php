<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Links y archivos del pedido (contexto `pedido`, pedidos.md §4.5): los baja quien puede ver el pedido
 * (fuera de alcance: 404) y los quita quien puede editarlo (RN-PED-28).
 */
final readonly class PedidoAdjuntos implements AutorizaAdjuntos
{
    public function __construct(private AccesoPedido $acceso) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        return $sujeto instanceof Pedido && $this->acceso->ver($user, $sujeto);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        return $sujeto instanceof Pedido && $this->acceso->editar($user, $sujeto);
    }
}
