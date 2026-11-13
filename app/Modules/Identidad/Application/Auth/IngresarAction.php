<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

/**
 * F-01: ingreso con mail + contraseña (RN-IDE-01…04). El mensaje de error no revela si el mail
 * existe; el inactivo recibe su propio mensaje solo si la contraseña es correcta.
 */
final class IngresarAction
{
    public const CREDENCIALES_INCORRECTAS = 'Mail o contraseña incorrectos.';

    public const USUARIO_INACTIVO = 'Tu usuario está inactivo. Si lo necesitás, pedíselo a gerencia.';

    public function __construct(
        private readonly SesionWeb $sesion,
        private readonly Hasher $hasher,
    ) {}

    public function handle(string $email, string $password, bool $recordarme): User
    {
        $user = User::query()->where('email', MailNormalizado::de($email))->first();

        if ($user === null || $user->password === null || ! $this->hasher->check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => [self::CREDENCIALES_INCORRECTAS]]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages(['email' => [self::USUARIO_INACTIVO]]);
        }

        $this->sesion->iniciar($user, $recordarme);

        return $user;
    }
}
