<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;

/**
 * RN-IDE-06: solo se manda el mail si el usuario existe y está activo; la respuesta es siempre
 * la misma (la arma el controller), así no se revela si el mail existe.
 */
final class SolicitarRecuperacionAction
{
    public function __construct(private readonly MailsDeCuenta $mails) {}

    public function handle(string $email): void
    {
        $user = User::query()
            ->where('email', MailNormalizado::de($email))
            ->where('activo', true)
            ->first();

        if ($user !== null) {
            $this->mails->enviarRecuperacion($user);
        }
    }
}
