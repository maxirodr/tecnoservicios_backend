<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

use App\Modules\Identidad\Domain\Models\User;

/**
 * Quién depende de quién ("Depende de", F-07): "tareas de mi gente" y aviso al superior.
 */
interface Jerarquia
{
    /**
     * @return list<int> ids de los subordinados, recursivos
     */
    public function subordinados(User $user): array;

    public function superior(User $user): ?User;
}
