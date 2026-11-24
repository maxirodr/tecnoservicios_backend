<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Carga\CargarGastoAction;
use App\Modules\Gastos\Application\Carga\MensajeDeCarga;
use App\Modules\Gastos\Application\Correccion\CorregirGastoAction;
use App\Modules\Gastos\Application\Lecturas\LecturaDeGasto;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Http\Requests\CargarGastoRequest;
use App\Modules\Gastos\Http\Requests\CorregirGastoRequest;
use App\Modules\Gastos\Http\Resources\GastoDetalleResource;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-39…F-47 · F-46 (W-10, W-34, W-35, W-46): cargar, ver y corregir. No hay ruta para borrar (RN-GAS-41). */
final class GastoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(
        private readonly BuscarGasto $buscar,
        private readonly LecturaDeGasto $lectura,
        private readonly Rubros $rubros,
    ) {}

    /** 201 con `GastoDetalle` + `obras_afectadas`. Lo cargado desde W-46 se devuelve entero (es de esa obra). */
    public function store(CargarGastoRequest $request, CargarGastoAction $cargar): JsonResponse
    {
        $user = $this->usuario($request);
        $cargado = $cargar->handle($user, $request->datos());
        $leido = $this->lectura->de(
            $user,
            $this->buscar->cargado($cargado->id, LecturaDeGasto::DETALLE),
            completo: $cargado->origen->seCargaDesdeLaRendicion(),
        );
        $obras = MensajeDeCarga::obrasAfectadas($leido);

        return $this->created(
            [...GastoDetalleResource::detalle($leido, $this->rubros), 'obras_afectadas' => $obras],
            MensajeDeCarga::de($cargado->origen, $obras),
        );
    }

    public function show(Request $request, int $gasto): JsonResponse
    {
        return $this->ok($this->detalle($this->usuario($request), $gasto));
    }

    public function update(CorregirGastoRequest $request, int $gasto, CorregirGastoAction $corregir): JsonResponse
    {
        $user = $this->usuario($request);
        $corregir->handle($user, $gasto, $request->cambios(), $request->versionEsperada());

        return $this->ok($this->detalle($user, $gasto), 'Guardamos los cambios.');
    }

    /**
     * @return array<string, mixed>
     */
    private function detalle(User $user, int $id): array
    {
        $gasto = $this->buscar->visible($user, $id, LecturaDeGasto::DETALLE);

        return GastoDetalleResource::detalle($this->lectura->de($user, $gasto), $this->rubros);
    }
}
