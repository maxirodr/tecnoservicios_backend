<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Controllers;

use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use App\Modules\Rendicion\Application\Materiales\AgregarMaterialAction;
use App\Modules\Rendicion\Application\Materiales\BuscarMaterial;
use App\Modules\Rendicion\Application\Materiales\ControlPorCantidadQuery;
use App\Modules\Rendicion\Application\Materiales\CorregirMaterialAction;
use App\Modules\Rendicion\Application\Materiales\QuitarMaterialAction;
use App\Modules\Rendicion\Application\Materiales\ReglasDeLaLista;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Http\Requests\GuardarMaterialRequest;
use App\Modules\Rendicion\Http\Requests\OpcionesDeMaterialesRequest;
use App\Modules\Rendicion\Http\Resources\MaterialResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F-66 (W-48): el control por cantidad (`control_cantidad` ≥ ver), la lista de materiales (`= editar`) y las
 * opciones para las cargas de Gastos (W-10, W-46). Todo con el alcance de la obra (RN-REN-55, 56).
 */
final class MaterialController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(
        private readonly AccesoRendicion $acceso,
        private readonly ReglasDeLaLista $reglas,
    ) {}

    public function control(Request $request, int $obra, ControlPorCantidadQuery $control): JsonResponse
    {
        $user = $this->usuario($request);

        return $this->ok(MaterialResource::control($control->handle($user, $this->acceso->obraDeControl($user, $obra, Nivel::Ver))));
    }

    public function opciones(OpcionesDeMaterialesRequest $request, int $obra, MaterialesDeObra $materiales): JsonResponse
    {
        $resumen = $this->acceso->obraParaOpciones($this->usuario($request), $obra);

        return $this->ok(array_map(MaterialResource::opcion(...), $materiales->opciones($resumen->id, $request->buscar())));
    }

    public function store(GuardarMaterialRequest $request, int $obra, AgregarMaterialAction $agregar): JsonResponse
    {
        $user = $this->usuario($request);
        $material = $agregar->handle($user, $this->acceso->obraDeControl($user, $obra, Nivel::Editar), $request->datos());

        return $this->created(MaterialResource::material($material, $this->reglas->numeroDe($material->obra_id, $material->presupuesto_id)), 'Material agregado a la lista.');
    }

    public function update(GuardarMaterialRequest $request, int $material, BuscarMaterial $buscar, CorregirMaterialAction $corregir): JsonResponse
    {
        $user = $this->usuario($request);
        $corregido = $corregir->handle($user, $buscar->editable($user, $material), $request->datos(), $request->versionEsperada());

        return $this->ok(MaterialResource::material($corregido, $this->reglas->numeroDe($corregido->obra_id, $corregido->presupuesto_id)), 'Material corregido.');
    }

    public function destroy(Request $request, int $material, BuscarMaterial $buscar, QuitarMaterialAction $quitar): JsonResponse
    {
        $quitar->handle($buscar->editable($this->usuario($request), $material));

        return $this->noContent('Material quitado de la lista.');
    }
}
