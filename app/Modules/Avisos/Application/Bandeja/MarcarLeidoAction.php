<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Bandeja;

use App\Modules\Avisos\Domain\Models\Aviso;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Marca leído uno de mis avisos. Un aviso ajeno no existe para mí (404, RN-AVI-26). Idempotente: uno ya
 * leído conserva la fecha original.
 */
final class MarcarLeidoAction
{
    /**
     * @throws ModelNotFoundException<Aviso>
     */
    public function handle(int $userId, int $avisoId): Aviso
    {
        $aviso = Aviso::query()->where('user_id', $userId)->findOrFail($avisoId);

        if ($aviso->leido_at === null) {
            Aviso::query()->whereKey($aviso->id)->whereNull('leido_at')->update(['leido_at' => now()]);
            $aviso->refresh();
        }

        return $aviso;
    }
}
