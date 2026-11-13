<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\MailsDeCuenta;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-09: reenvía el mail de bienvenida con un link nuevo de 7 días (el anterior deja de
 * servir). Solo para usuarios activos que todavía no eligieron contraseña.
 */
final class ReenviarBienvenidaAction
{
    public const USUARIO_INACTIVO = 'El usuario está inactivo: activalo antes de reenviarle el mail.';

    public const YA_ELIGIO_CONTRASENA = "Ya eligió su contraseña: puede usar 'Olvidé mi contraseña'.";

    public function __construct(private readonly MailsDeCuenta $mails) {}

    public function handle(User $actor, User $usuario): void
    {
        if (! $usuario->activo) {
            throw ValidationException::withMessages(['usuario' => [self::USUARIO_INACTIVO]]);
        }

        if ($usuario->eligioContrasena()) {
            throw ValidationException::withMessages(['usuario' => [self::YA_ELIGIO_CONTRASENA]]);
        }

        $this->mails->enviarBienvenida($usuario, $actor->nombre);
    }
}
