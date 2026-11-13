<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\BloqueosDeIngreso;
use App\Modules\Identidad\Infrastructure\CodigosDeIngreso;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * F-01 (ADR-015, RN-IDE-02/03), fuera del pedido HTTP: busca al usuario activo y, si existe y su mail
 * no está bloqueado (SEG-01), emite el código (invalida el anterior) y encola el mail. Si no, no hace
 * nada: la respuesta HTTP ya salió igual para todos. Corre en la cola `mail`, la misma de la que ya
 * depende el ingreso.
 */
final class EnviarCodigoJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public const COLA = 'mail';

    public function __construct(public readonly string $emailNormalizado)
    {
        $this->onQueue(self::COLA);
    }

    public function handle(CodigosDeIngreso $codigos, MailsDeCuenta $mails, BloqueosDeIngreso $bloqueos): void
    {
        $user = User::query()
            ->where('email', $this->emailNormalizado)
            ->where('activo', true)
            ->first();

        if ($user === null || $bloqueos->bloqueado($user)) {
            return;
        }

        DB::transaction(static function () use ($user, $codigos, $mails): void {
            $mails->enviarCodigo($user, $codigos->emitir($user));
        });
    }
}
