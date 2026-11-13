<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Montos\CargarMontoObraAction;
use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Application\Montos\PresupuestoPorRubroQuery;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Http\Controllers\Concerns\ConUsuario;
use App\Modules\Obras\Http\Requests\CargarMontoObraRequest;
use App\Modules\Obras\Http\Requests\CargarMontosRequest;
use App\Modules\Obras\Http\Resources\PresupuestoResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F-48 / F-54 dato (W-13): presupuesto por rubro de la obra y monto de cada presupuesto. Ver y cargar
 * dependen de `presupuesto_rubro` y `monto_obra` con su alcance (obras.md §7); la obra tiene que ser visible.
 */
final class PresupuestoMontosController
{
    use ConUsuario;
    use RespondsWithEnvelope;

    public function __construct(
        private readonly AccesoObra $acceso,
        private readonly BuscarObra $buscar,
    ) {}

    public function show(Request $request, int $obra, PresupuestoPorRubroQuery $consulta): JsonResponse
    {
        $user = $this->usuario($request);
        $visible = $this->buscar->visible($user, $obra);
        $this->buscar->exigir($this->acceso->verMontos($user, $visible));

        return $this->ok($consulta->handle($visible, $this->acceso->verMontoObra($user, $visible)));
    }

    public function montos(CargarMontosRequest $request, int $presupuesto, CargarMontosAction $cargar, PresupuestoPorRubroQuery $consulta): JsonResponse
    {
        $user = $this->usuario($request);
        $encontrado = $this->buscar->presupuestoVisible($user, $presupuesto);
        $this->buscar->exigir($this->acceso->cargarMontos($user, $encontrado->obra));

        $cargar->handle($encontrado, $request->modo(), $request->montos());

        return $this->ok($consulta->handle($encontrado->obra, $this->acceso->verMontoObra($user, $encontrado->obra)), 'Guardamos los montos.');
    }

    public function montoObra(CargarMontoObraRequest $request, int $presupuesto, CargarMontoObraAction $cargar): JsonResponse
    {
        $user = $this->usuario($request);
        $encontrado = $this->buscar->presupuestoVisible($user, $presupuesto);
        $this->buscar->exigir($this->acceso->cargarMontoObra($user, $encontrado->obra));

        $cargar->handle($encontrado, $request->montoObra());
        $encontrado = Presupuesto::query()->with(['adjuntos.subidoPor', 'creadoPor'])->findOrFail($encontrado->id);

        return $this->ok(new PresupuestoResource($encontrado, true), 'Guardamos el monto del presupuesto.');
    }
}
