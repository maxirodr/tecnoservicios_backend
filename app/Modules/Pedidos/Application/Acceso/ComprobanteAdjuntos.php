<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AutorizaCadaAdjunto;
use Illuminate\Database\Eloquent\Model;

/**
 * Comprobantes de cierre (contexto `comprobante_cierre`): los ve quien ve el pedido, también después de
 * reabrir; los quita **solo quien completó ese cierre** (RN-PED-21), que es quien los subió al completar.
 */
final readonly class ComprobanteAdjuntos implements AutorizaCadaAdjunto
{
    public function __construct(private AccesoPedido $acceso) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        return $sujeto instanceof Pedido && $this->acceso->ver($user, $sujeto);
    }

    /** Sin el adjunto no se sabe de qué cierre es: nadie. */
    public function puedeEditar(User $user, Model $sujeto): bool
    {
        return false;
    }

    public function puedeEditarAdjunto(User $user, Model $sujeto, Adjunto $adjunto): bool
    {
        return $adjunto->esDe($user) && $this->puedeVer($user, $sujeto);
    }
}
