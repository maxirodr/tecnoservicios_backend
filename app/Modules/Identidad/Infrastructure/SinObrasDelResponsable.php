<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Implementación por defecto hasta que el módulo Obras registre la suya (todavía no hay obras).
 */
final class SinObrasDelResponsable implements ObrasDelResponsable
{
    public function cantidad(User $user): int
    {
        return 0;
    }
}
