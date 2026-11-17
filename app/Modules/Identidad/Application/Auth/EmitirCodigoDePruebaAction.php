<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\BloqueosDeIngreso;
use App\Modules\Identidad\Infrastructure\CodigosDeIngreso;

/**
 * SOLO servidores de prueba (`IDENTIDAD_CODIGO_EN_PANTALLA=true`): emite el código en el momento y lo
 * devuelve para mostrarlo en pantalla, sin mandar mail. Mismas reglas que EnviarCodigoJob (usuario
 * activo, mail no bloqueado). Revela si el mail existe y deja entrar sin acceso al mail: nunca en prod.
 */
final class EmitirCodigoDePruebaAction
{
    public function __construct(
        private readonly CodigosDeIngreso $codigos,
        private readonly BloqueosDeIngreso $bloqueos,
    ) {}

    public function handle(string $email): ?string
    {
        $user = User::query()
            ->where('email', MailNormalizado::de($email))
            ->where('activo', true)
            ->first();

        if ($user === null || $this->bloqueos->bloqueado($user)) {
            return null;
        }

        return $this->codigos->emitir($user);
    }
}
