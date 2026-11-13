<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Perfil;

use App\Modules\Identidad\Application\Permisos\CeldasDelSector;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Arma el UsuarioActual leyendo la matriz en cada pedido (RN-IDE-14: el front lo vuelve a pedir
 * al enfocar la pestaña y al recibir un 403).
 */
final class UsuarioActualQuery
{
    public function __construct(private readonly CeldasDelSector $celdas) {}

    public function handle(User $user): UsuarioActual
    {
        $user->load(['sector', 'jefe.sector']);

        return new UsuarioActual(
            $user,
            $user->dependientes()->where('activo', true)->exists(),
            $this->celdas->handle($user->sector_id),
        );
    }
}
