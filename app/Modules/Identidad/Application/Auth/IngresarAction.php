<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\CodigosDeIngreso;
use Illuminate\Validation\ValidationException;

/**
 * F-01 (ADR-015): entrar con el mail y el código que llegó por mail (RN-IDE-01, 03…05). Código
 * incorrecto, vencido o usado, usuario inactivo o mail inexistente: siempre el mismo mensaje.
 */
final class IngresarAction
{
    public const CODIGO_INVALIDO = 'El código no es válido o venció.';

    public function __construct(
        private readonly CodigosDeIngreso $codigos,
        private readonly SesionWeb $sesion,
    ) {}

    public function handle(string $email, string $codigo): User
    {
        $user = User::query()
            ->where('email', MailNormalizado::de($email))
            ->where('activo', true)
            ->first();

        if ($user === null || ! $this->codigos->canjear($user, $codigo)) {
            throw ValidationException::withMessages(['codigo' => [self::CODIGO_INVALIDO]]);
        }

        $this->sesion->iniciar($user);

        return $user;
    }
}
