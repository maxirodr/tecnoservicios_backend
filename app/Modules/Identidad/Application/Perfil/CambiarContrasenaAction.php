<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Perfil;

use App\Modules\Identidad\Application\Auth\SesionWeb;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-17: pide la actual; la nueva no puede ser igual. Cierra las demás sesiones (cambia el hash
 * que guarda cada sesión y la cookie "recordarme" de los otros equipos); esta sesión sigue.
 */
final class CambiarContrasenaAction
{
    public const ACTUAL_NO_COINCIDE = 'La contraseña actual no coincide.';

    public const NUEVA_IGUAL_A_ACTUAL = 'La nueva contraseña tiene que ser distinta de la actual.';

    private const LARGO_REMEMBER_TOKEN = 60;

    public function __construct(
        private readonly Hasher $hasher,
        private readonly SesionWeb $sesion,
    ) {}

    public function handle(User $user, string $actual, string $nueva, bool $recordado): void
    {
        $hashActual = (string) $user->password;

        if (! $this->hasher->check($actual, $hashActual)) {
            throw ValidationException::withMessages(['actual' => [self::ACTUAL_NO_COINCIDE]]);
        }

        if ($this->hasher->check($nueva, $hashActual)) {
            throw ValidationException::withMessages(['nueva' => [self::NUEVA_IGUAL_A_ACTUAL]]);
        }

        $user->password = $nueva;
        $user->setRememberToken(Str::random(self::LARGO_REMEMBER_TOKEN));
        $user->save();

        if ($recordado) {
            // Este equipo conserva su "recordarme" con el token nuevo.
            $this->sesion->iniciar($user, true);
        }
    }
}
