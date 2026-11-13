<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Presupuestos\ActualizarPresupuestoAction;
use App\Modules\Obras\Application\Presupuestos\AgregarPresupuestoAction;
use App\Modules\Obras\Application\Presupuestos\QuitarPresupuestoAction;
use App\Modules\Obras\Application\Presupuestos\SugerirNumeroAdicionalQuery;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Http\Requests\ActualizarPresupuestoRequest;
use App\Modules\Obras\Http\Requests\AgregarPresupuestoRequest;
use App\Modules\Obras\Http\Resources\PresupuestoResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-18 (W-20, W-21): presupuestos adicionales y corrección de cualquier presupuesto. */
final class PresupuestoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(private readonly AccesoObra $acceso) {}

    public function store(AgregarPresupuestoRequest $request, int $obra, BuscarObra $buscar, AgregarPresupuestoAction $agregar): JsonResponse
    {
        $user = $this->usuario($request);
        $presupuesto = $agregar->handle($user, $buscar->editable($user, $obra), $request->datos());

        return $this->created($this->recurso($user, $presupuesto), 'Agregamos el presupuesto adicional.');
    }

    public function sugerirNumero(Request $request, int $obra, BuscarObra $buscar, SugerirNumeroAdicionalQuery $sugerir): JsonResponse
    {
        return $this->ok(['numero' => $sugerir->handle($buscar->visible($this->usuario($request), $obra))]);
    }

    public function update(ActualizarPresupuestoRequest $request, int $presupuesto, BuscarObra $buscar, ActualizarPresupuestoAction $actualizar): JsonResponse
    {
        $user = $this->usuario($request);
        $actualizado = $actualizar->handle($buscar->presupuestoEditable($user, $presupuesto), $request->datos());

        return $this->ok($this->recurso($user, $actualizado), 'Guardamos los cambios.');
    }

    public function destroy(Request $request, int $presupuesto, BuscarObra $buscar, QuitarPresupuestoAction $quitar): JsonResponse
    {
        $quitar->handle($buscar->presupuestoEditable($this->usuario($request), $presupuesto));

        return $this->noContent('Quitamos el presupuesto adicional.');
    }

    private function recurso(User $user, Presupuesto $presupuesto): PresupuestoResource
    {
        $presupuesto = Presupuesto::query()->with(['obra', 'adjuntos.subidoPor', 'creadoPor'])->findOrFail($presupuesto->id);

        return new PresupuestoResource($presupuesto, $this->acceso->verMontoObra($user, $presupuesto->obra));
    }
}
