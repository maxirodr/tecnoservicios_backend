<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use LogicException;

/**
 * Sesión de Sanctum SPA (guard `web`, cookie): iniciar y cerrar. Dura 90 días desde el último uso
 * (config/session.php, RN-IDE-07); no hay "recordarme".
 */
final class SesionWeb
{
    private const GUARD = 'web';

    public function __construct(private readonly AuthFactory $auth) {}

    public function iniciar(User $user): void
    {
        $this->guard()->login($user);
    }

    public function cerrar(): void
    {
        $this->guard()->logout();
    }

    private function guard(): SessionGuard
    {
        $guard = $this->auth->guard(self::GUARD);

        if (! $guard instanceof SessionGuard) {
            throw new LogicException('El guard web tiene que ser de sesión (Sanctum SPA).');
        }

        return $guard;
    }
}
