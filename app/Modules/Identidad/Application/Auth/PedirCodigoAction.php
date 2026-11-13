<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\CodigosDeIngreso;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;
use Illuminate\Support\Facades\DB;

/**
 * F-01 (ADR-015, RN-IDE-02/03): solo un usuario activo recibe el código; la respuesta es siempre
 * la misma (la arma el controller), así no se revela si el mail existe. Pedir otro invalida el anterior.
 */
final class PedirCodigoAction
{
    public const RESPUESTA = 'Si el mail está registrado, te llegó un código.';

    public function __construct(
        private readonly CodigosDeIngreso $codigos,
        private readonly MailsDeCuenta $mails,
    ) {}

    public function handle(string $email): void
    {
        $user = User::query()
            ->where('email', MailNormalizado::de($email))
            ->where('activo', true)
            ->first();

        if ($user === null) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $this->mails->enviarCodigo($user, $this->codigos->emitir($user));
        });
    }
}
