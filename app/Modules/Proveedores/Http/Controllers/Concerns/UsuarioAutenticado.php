<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Controllers\Concerns;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

trait UsuarioAutenticado
{
    protected function usuario(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
