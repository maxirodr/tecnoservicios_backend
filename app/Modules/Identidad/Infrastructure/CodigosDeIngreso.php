<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Códigos de ingreso por mail (ADR-015): se guarda solo su HMAC-SHA256 con la APP_KEY, nunca el
 * código en claro (es rápido: no suma una demora que delate si el mail existe). Emitir uno nuevo
 * reemplaza al anterior; canjearlo bien lo borra (un solo uso); cada intento fallido suma y al
 * quinto se borra ("quemado", que cuenta para el bloqueo progresivo de SEG-01). El canje bloquea la
 * fila para que dos pedidos simultáneos no usen el mismo. Con el mail bloqueado no se canjea nada.
 */
final class CodigosDeIngreso
{
    private const MAXIMO = 10 ** CodigoIngreso::DIGITOS - 1;

    private const ALGORITMO = 'sha256';

    public function __construct(
        #[Config('app.key')] private readonly string $clave,
        private readonly BloqueosDeIngreso $bloqueos,
    ) {}

    /** Crea un código nuevo para la persona (invalida el anterior) y devuelve el código en claro para el mail. */
    public function emitir(User $user): string
    {
        $codigo = str_pad((string) random_int(0, self::MAXIMO), CodigoIngreso::DIGITOS, '0', STR_PAD_LEFT);

        CodigoIngreso::query()->updateOrCreate(['user_id' => $user->id], [
            'codigo_hash' => $this->hash($codigo),
            'intentos_fallidos' => 0,
            'vence_at' => Carbon::now()->addMinutes(CodigoIngreso::VIGENCIA_MINUTOS),
        ]);

        return $codigo;
    }

    /** true si el código es el vigente de la persona; en ese caso deja de servir. */
    public function canjear(User $user, string $codigo): bool
    {
        return DB::transaction(function () use ($user, $codigo): bool {
            $vigente = CodigoIngreso::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($vigente === null || $this->bloqueos->bloqueado($user)) {
                return false;
            }

            if ($vigente->vencido()) {
                $vigente->delete();

                return false;
            }

            if (hash_equals($vigente->codigo_hash, $this->hash($codigo))) {
                $vigente->delete();
                $this->bloqueos->reiniciar($user);

                return true;
            }

            $this->registrarFallo($user, $vigente);

            return false;
        });
    }

    private function hash(string $codigo): string
    {
        return hash_hmac(self::ALGORITMO, $codigo, $this->clave);
    }

    private function registrarFallo(User $user, CodigoIngreso $vigente): void
    {
        $vigente->intentos_fallidos++;

        if ($vigente->intentos_fallidos >= CodigoIngreso::INTENTOS_MAXIMOS) {
            $vigente->delete();
            $this->bloqueos->registrarQuemado($user);

            return;
        }

        $vigente->save();
    }
}
