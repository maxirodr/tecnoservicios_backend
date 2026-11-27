<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Carga\FacturaRepetida;
use App\Modules\Gastos\Application\Lecturas\EmpleadosDelParteQuery;
use App\Modules\Gastos\Application\Lecturas\OpcionesDeCargaQuery;
use App\Modules\Gastos\Domain\NumeroComprobante;
use App\Modules\Gastos\Http\Requests\BuscarObrasRequest;
use App\Modules\Gastos\Http\Requests\FacturaExistenteRequest;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** Lo que necesita el formulario de carga (gastos.md §12.1): rubros, obras y el aviso de factura repetida. */
final class GastoOpcionesController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function opciones(OpcionesDeCargaQuery $opciones): JsonResponse
    {
        return $this->ok($opciones->opciones());
    }

    public function obras(BuscarObrasRequest $request, OpcionesDeCargaQuery $opciones): JsonResponse
    {
        return $this->ok($opciones->obras($this->usuario($request), $request->buscar()));
    }

    /** RN-GAS-56: empleados ya cargados en partes de horas (`gastos = editar`, con su alcance). */
    public function empleados(BuscarObrasRequest $request, EmpleadosDelParteQuery $empleados): JsonResponse
    {
        return $this->ok($empleados->handle($this->usuario($request), $request->buscar()));
    }

    /** RN-GAS-08: al salir del campo Número o al elegir el proveedor; con alcance (§8.2). */
    public function facturaExistente(FacturaExistenteRequest $request, FacturaRepetida $facturas): JsonResponse
    {
        return $this->ok($facturas->aviso(
            $this->usuario($request),
            $request->proveedorId(),
            NumeroComprobante::normalizar($request->numero()),
            $request->excluirId(),
        ));
    }
}
