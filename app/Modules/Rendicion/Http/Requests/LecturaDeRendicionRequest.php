<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use Illuminate\Auth\AuthenticationException;

/**
 * W-36 y W-37 con filtros: sin `rendicion` ≥ ver, 403 antes de validar los filtros (un `?orden=x` no
 * responde 422 a quien no tiene acceso). El alcance por obra (404) lo sigue mirando `AccesoRendicion`.
 */
abstract class LecturaDeRendicionRequest extends RendicionRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        $this->container->make(AccesoRendicion::class)->exigirRendicion($user);

        return true;
    }
}
