<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Domain\Models\User;

/**
 * RN-IDE-24: lo que la ventana de W-39 avisa antes de desactivar a alguien ("tiene 2 personas a
 * cargo y es responsable de 3 obras"). No bloquea nada.
 */
final class ACargoDelUsuarioQuery
{
    public function __construct(private readonly ObrasDelResponsable $obras) {}

    /**
     * @return array{personas_a_cargo: int, obras_como_responsable: int}
     */
    public function handle(User $user): array
    {
        return [
            'personas_a_cargo' => $user->dependientes()->where('activo', true)->count(),
            'obras_como_responsable' => $this->obras->cantidad($user),
        ];
    }
}
