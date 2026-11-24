<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Carga\CargaRepetida;
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

    /** RN-GAS-08: al salir del campo Número o al elegir el proveedor. */
    public function facturaExistente(FacturaExistenteRequest $request, CargaRepetida $repetida): JsonResponse
    {
        $existente = $repetida->facturaExistente($request->proveedorId(), NumeroComprobante::normalizar($request->numero()), $request->excluirId());

        if ($existente === null) {
            return $this->ok(['existe' => false]);
        }

        return $this->ok([
            'existe' => true,
            'gasto' => [
                'id' => $existente->id,
                'numero' => $existente->numero,
                'fecha' => $existente->fecha->toDateString(),
                'cargado_por' => ['id' => $existente->cargadoPor->id, 'nombre' => $existente->cargadoPor->nombre],
            ],
            'mensaje' => CargaRepetida::mensajeDeFacturaCargada($existente),
        ]);
    }
}
