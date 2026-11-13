<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use LogicException;

/**
 * Links para elegir contraseña (ADR-009, RN-IDE-07/09) sobre los brokers de Laravel:
 * `users` = recuperación (60 min) y `bienvenida` = alta (7 días). Crear uno nuevo invalida el
 * anterior del mismo tipo; al usarse se borran los dos (sirve una sola vez).
 */
final class TokensDeContrasena
{
    private const RECUPERACION = 'users';

    private const BIENVENIDA = 'bienvenida';

    public function __construct(private readonly PasswordBrokerManager $brokers) {}

    public function paraRecuperacion(User $user): string
    {
        return $this->broker(self::RECUPERACION)->createToken($user);
    }

    public function paraBienvenida(User $user): string
    {
        return $this->broker(self::BIENVENIDA)->createToken($user);
    }

    public function esValido(User $user, string $token): bool
    {
        return $this->broker(self::RECUPERACION)->tokenExists($user, $token)
            || $this->broker(self::BIENVENIDA)->tokenExists($user, $token);
    }

    public function invalidar(User $user): void
    {
        $this->broker(self::RECUPERACION)->deleteToken($user);
        $this->broker(self::BIENVENIDA)->deleteToken($user);
    }

    private function broker(string $nombre): PasswordBroker
    {
        $broker = $this->brokers->broker($nombre);

        if (! $broker instanceof PasswordBroker) {
            throw new LogicException("El broker de contraseñas '{$nombre}' no es el de Laravel.");
        }

        return $broker;
    }
}
