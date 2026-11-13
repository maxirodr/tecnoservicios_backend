<?php

declare(strict_types=1);

namespace App\Shared\Busqueda\Http;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Busqueda\BuscadorGeneral;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

/** `GET /api/buscar?q=` (F-03 · W-18): grupos por tipo según los permisos de quien busca. */
final class BuscadorController
{
    use RespondsWithEnvelope;

    public function __invoke(BuscarRequest $request, BuscadorGeneral $buscador): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->ok(['grupos' => $buscador->handle($user, $request->texto())]);
    }
}
