<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * El usuario del pedido, o 401 (el envelope lo arma ApiExceptionRenderer). Uno solo para todos los
 * controllers (TL-06).
 */
trait UsuarioAutenticado
{
    /**
     * @throws AuthenticationException
     */
    protected function usuario(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
