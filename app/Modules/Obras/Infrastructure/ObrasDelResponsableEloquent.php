<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;

/**
 * Puerto de Identidad (RN-IDE-24): obras activas (Confirmadas y En ejecución) donde la persona es
 * responsable, para el aviso al desactivarla. Las Terminadas no se cuentan: no hay nada que reasignar.
 */
final class ObrasDelResponsableEloquent implements ObrasDelResponsable
{
    public function cantidad(User $user): int
    {
        return Obra::query()
            ->where('responsable_id', $user->id)
            ->where('estado', '!=', ObraEstado::Terminada->value)
            ->count();
    }
}
