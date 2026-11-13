<?php

declare(strict_types=1);

namespace App\Shared\Busqueda;

use App\Modules\Identidad\Domain\Models\User;

/**
 * Un grupo del buscador general (F-03, W-18): Obras, Proveedores (semana 1), Pedidos (2), Gastos (3).
 * Cada módulo dueño la implementa con su query y su permiso, y la registra en `FuentesBusqueda`
 * desde el boot() de su provider.
 */
interface FuenteBusqueda
{
    /** Sin acceso al módulo, el grupo no aparece ni viaja en la respuesta (HU-022 crit. 3 y 6). */
    public function puedeBuscar(User $user): bool;

    /** `$texto` ya viene recortado y con al menos 2 caracteres; respeta el alcance de `$user`. */
    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda;
}
