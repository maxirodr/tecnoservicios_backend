<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Bandeja;

use App\Modules\Avisos\Domain\Models\Aviso;

/** "Marcar todo como leído": mis no leídos pasan a leídos y la campana queda en 0 (HU-049 c3). */
final class MarcarTodosLeidosAction
{
    public function handle(int $userId): int
    {
        return Aviso::query()->where('user_id', $userId)->whereNull('leido_at')->update(['leido_at' => now()]);
    }
}
