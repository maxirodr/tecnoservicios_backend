<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Los adjuntos heredan el permiso del recurso dueño (identidad.md §6.6). Cada módulo dueño implementa
 * esta interfaz con su Policy/matriz y la registra por contexto en `ContextosAdjuntos`.
 */
interface AutorizaAdjuntos
{
    /** Puede ver el recurso dueño, con su alcance: puede bajar el adjunto (RN-IDE-47). */
    public function puedeVer(User $user, Model $sujeto): bool;

    /** Puede editar el recurso dueño: puede quitar el adjunto (RN-IDE-48). */
    public function puedeEditar(User $user, Model $sujeto): bool;
}
