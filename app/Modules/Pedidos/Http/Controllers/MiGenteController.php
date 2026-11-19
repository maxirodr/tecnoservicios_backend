<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Pedidos\Application\Lecturas\CargaPorPersona;
use App\Modules\Pedidos\Application\Lecturas\TareasDeMiGente;
use App\Modules\Pedidos\Http\Requests\CargaPorPersonaRequest;
use App\Modules\Pedidos\Http\Requests\EstadoDeListaRequest;
use App\Modules\Pedidos\Http\Resources\MiGenteResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/**
 * F-06 · F-12 · F-61 (W-26, W-04): las vistas de jefatura. Dependen de la jerarquía, no de la matriz:
 * sin gente a cargo (ni Dirección), 403 (RN-PED-35).
 */
final class MiGenteController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    /** Un grupo por persona con todos sus pedidos del estado pedido (el front muestra 3). */
    public function tareas(EstadoDeListaRequest $request, TareasDeMiGente $tareas): JsonResponse
    {
        return $this->ok(array_map(MiGenteResource::grupo(...), $tareas->grupos($this->usuario($request), $request->estado())));
    }

    public function carga(CargaPorPersonaRequest $request, CargaPorPersona $carga): JsonResponse
    {
        $resultado = $carga->handle($this->usuario($request), $request->sectorId());

        return $this->ok([
            'semana' => $resultado['semana'],
            'personas' => array_map(MiGenteResource::fila(...), $resultado['personas']),
        ]);
    }
}
