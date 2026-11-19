<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Controllers;

use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Pedidos\Application\Pedidos\CalculoDePrioridad;
use App\Modules\Pedidos\Domain\Prioridad\Anticipacion;
use App\Modules\Pedidos\Http\Requests\BuscarRequest;
use App\Modules\Pedidos\Http\Requests\PrioridadSugeridaRequest;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;

/** Lo que necesita el formulario W-08: la prioridad que va a calcular el sistema y el selector de obras. */
final class PedidoOpcionesController
{
    use RespondsWithEnvelope;

    private const MAX_OBRAS = 20;

    /** RN-PED-11: "Prioritario · 4 días entre el pedido y la entrega" con los plazos vigentes. */
    public function prioridadSugerida(PrioridadSugeridaRequest $request, CalculoDePrioridad $calculo): JsonResponse
    {
        $pedido = $request->fechaPedido();
        $entrega = $request->fechaEntrega();

        return $this->ok([
            'prioridad' => $calculo->calculada($pedido, $entrega)?->value,
            'dias' => Anticipacion::dias($pedido, $entrega),
        ]);
    }

    /** Resuelta DU-P20: todas las obras (también terminadas), con Pedidos = Editar aunque no vea Obras. */
    public function obras(BuscarRequest $request, ObrasConsulta $obras): JsonResponse
    {
        return $this->ok(array_map(static fn (ObraResumen $o): array => [
            'id' => $o->id,
            'numero' => $o->numero,
            'nombre_trabajo' => $o->nombreTrabajo,
            'cliente' => $o->cliente,
            'estado' => $o->estado,
        ], $obras->opciones($request->buscar(), self::MAX_OBRAS)));
    }
}
