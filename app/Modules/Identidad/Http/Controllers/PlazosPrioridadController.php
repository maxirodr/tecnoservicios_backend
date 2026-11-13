<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Plazos\ActualizarPlazosAction;
use App\Modules\Identidad\Domain\Models\Plazos;
use App\Modules\Identidad\Http\Controllers\Concerns\UsuarioAutenticado;
use App\Modules\Identidad\Http\Requests\ActualizarPlazosRequest;
use App\Modules\Identidad\Http\Resources\PlazosResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** F-68 · W-50: plazos de la prioridad. */
final class PlazosPrioridadController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function show(): JsonResponse
    {
        return $this->ok(new PlazosResource(Plazos::query()->with('editor')->firstOrFail()));
    }

    public function update(ActualizarPlazosRequest $request, ActualizarPlazosAction $actualizar): JsonResponse
    {
        $plazos = $actualizar->handle($this->usuario($request), $request->urgente(), $request->prioritario());

        return $this->ok(new PlazosResource($plazos->load('editor')), 'Guardamos los plazos.');
    }
}
