<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Bandeja;

use App\Modules\Avisos\Domain\Models\Aviso;

/** El número de la campana: mis avisos no leídos, sobre el índice `user_id, leido_at` (RN-AVI-22, ADR-005). */
final class ContarNoLeidosQuery
{
    public function handle(int $userId): int
    {
        return Aviso::query()->where('user_id', $userId)->whereNull('leido_at')->count();
    }
}
