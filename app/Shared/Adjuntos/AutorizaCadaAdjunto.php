<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Para los contextos donde quitar depende del adjunto y no solo del recurso dueño: un comprobante de
 * cierre lo quita solo quien completó ese cierre, que es quien lo subió (pedidos.md RN-PED-21).
 * Si el autorizador del contexto implementa esta interfaz, `AccesoAdjuntos::puedeEditar` la usa.
 */
interface AutorizaCadaAdjunto extends AutorizaAdjuntos
{
    public function puedeEditarAdjunto(User $user, Model $sujeto, Adjunto $adjunto): bool;
}
