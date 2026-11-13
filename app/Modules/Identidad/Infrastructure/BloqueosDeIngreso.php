<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Domain\Models\BloqueoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Persistencia del bloqueo progresivo (SEG-01). Se llama dentro de la transacción del canje, con la
 * fila del código ya bloqueada, así dos fallos simultáneos no se pisan el contador.
 */
final class BloqueosDeIngreso
{
    public function bloqueado(User $user): bool
    {
        return BloqueoIngreso::query()->where('user_id', $user->id)->first()?->bloqueado() ?? false;
    }

    public function registrarQuemado(User $user): void
    {
        $bloqueo = BloqueoIngreso::query()->lockForUpdate()->firstOrNew(['user_id' => $user->id]);
        $bloqueo->registrarQuemado();
        $bloqueo->save();

        if ($bloqueo->bloqueado()) {
            // Log de seguridad: sin el mail (SEG-02), solo el id y hasta cuándo.
            Log::warning('ingreso.bloqueado', [
                'user_id' => $user->id,
                'hasta' => $bloqueo->bloqueado_hasta?->toIso8601String(),
                'bloqueos_cumplidos' => $bloqueo->bloqueos_cumplidos,
            ]);
        }
    }

    /** Ingreso exitoso: el contador vuelve a cero. */
    public function reiniciar(User $user): void
    {
        BloqueoIngreso::query()->where('user_id', $user->id)->delete();
    }
}
