<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

use App\Modules\Identidad\Domain\Models\User;

/**
 * Puerto que implementa el módulo Obras: cuántas obras tiene una persona como responsable.
 * Identidad lo muestra al desactivarla (RN-IDE-24, `GET /api/usuarios/{id}`). Mientras Obras no
 * registre su implementación, Identidad usa una que devuelve 0.
 */
interface ObrasDelResponsable
{
    public function cantidad(User $user): int;
}
