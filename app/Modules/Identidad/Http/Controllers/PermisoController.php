<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Permisos\CambiarCeldasAction;
use App\Modules\Identidad\Application\Permisos\MatrizQuery;
use App\Modules\Identidad\Http\Requests\CambiarPermisosRequest;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** F-60 · W-15: tabla de permisos sector × función. */
final class PermisoController
{
    use RespondsWithEnvelope;

    public function index(MatrizQuery $matriz): JsonResponse
    {
        return $this->ok($matriz->handle());
    }

    public function update(CambiarPermisosRequest $request, CambiarCeldasAction $cambiar, MatrizQuery $matriz): JsonResponse
    {
        $cambiar->handle($request->celdas());

        return $this->ok($matriz->handle(), 'Guardamos la tabla de permisos.');
    }
}
