<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\TokensDeContrasena;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Elegir contraseña desde el link del mail (recuperación o bienvenida): RN-IDE-07/08/09.
 * El link sirve una vez; cambiar la contraseña invalida las demás sesiones (el hash de la sesión
 * ya no coincide) y la cookie "recordarme" de otros equipos. La persona queda adentro.
 */
final class ElegirContrasenaAction
{
    public const LINK_VENCIDO = "El link venció. Pedí uno nuevo desde 'Olvidé mi contraseña'.";

    private const LARGO_REMEMBER_TOKEN = 60;

    public function __construct(
        private readonly TokensDeContrasena $tokens,
        private readonly SesionWeb $sesion,
    ) {}

    public function handle(string $email, string $token, string $password): User
    {
        $user = User::query()
            ->where('email', MailNormalizado::de($email))
            ->where('activo', true)
            ->first();

        if ($user === null || ! $this->tokens->esValido($user, $token)) {
            throw ValidationException::withMessages(['token' => [self::LINK_VENCIDO]]);
        }

        DB::transaction(function () use ($user, $password): void {
            $user->password = $password;
            $user->setRememberToken(Str::random(self::LARGO_REMEMBER_TOKEN));
            $user->save();

            $this->tokens->invalidar($user);
        });

        $this->sesion->iniciar($user, false);

        return $user;
    }
}
