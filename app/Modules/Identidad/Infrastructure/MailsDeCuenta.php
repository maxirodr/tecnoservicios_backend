<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\BienvenidaMail;
use App\Modules\Identidad\Infrastructure\Mail\RecuperacionMail;
use Illuminate\Support\Facades\Mail;

/**
 * Mails de cuenta (RN-IDE-10): no son "avisos", salen desde la semana 1, por la cola `mail`.
 * El link apunta a la pantalla "elegir contraseña" del front con el token y el mail.
 */
final class MailsDeCuenta
{
    private const RUTA_ELEGIR_CONTRASENA = '/elegir-contrasena';

    public function __construct(private readonly TokensDeContrasena $tokens) {}

    public function enviarBienvenida(User $user, ?string $altaPor): void
    {
        $url = $this->url($this->tokens->paraBienvenida($user), $user->email);

        Mail::to($user->email)->queue(new BienvenidaMail($user->nombre, $user->email, $altaPor, $url));
    }

    public function enviarRecuperacion(User $user): void
    {
        $url = $this->url($this->tokens->paraRecuperacion($user), $user->email);

        Mail::to($user->email)->queue(new RecuperacionMail($user->nombre, $url));
    }

    private function url(string $token, string $email): string
    {
        $frontend = config('identidad.frontend_url');

        return (is_string($frontend) ? $frontend : '').self::RUTA_ELEGIR_CONTRASENA.'?'
            .http_build_query(['token' => $token, 'email' => $email]);
    }
}
