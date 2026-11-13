<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Perfil;

use App\Modules\Identidad\Domain\Models\User;

/**
 * RN-IDE-16: en Mi perfil la persona cambia solo su nombre y su teléfono.
 */
final class ActualizarPerfilAction
{
    /**
     * @param  array{nombre?: string, telefono?: string|null}  $datos
     */
    public function handle(User $user, array $datos): User
    {
        $user->fill(array_intersect_key($datos, array_flip(['nombre', 'telefono'])));
        $user->save();

        return $user;
    }
}
