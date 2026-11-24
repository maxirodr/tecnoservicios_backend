<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;

/**
 * Escribe los renglones del gasto en el orden en que se cargaron. Lo que "No está en la lista" lo crea
 * Rendicion como material fuera de presupuesto de la obra, en la misma transacción (RN-GAS-14, resuelta
 * DU-G03; si ya existe con ese nombre, usa ese).
 */
final readonly class GuardarRenglones
{
    public function __construct(private MaterialesDeObra $materiales) {}

    /**
     * @param  list<RenglonAGuardar>  $renglones
     */
    public function crear(Gasto $gasto, array $renglones, int $userId): void
    {
        foreach ($renglones as $orden => $renglon) {
            $this->escribir(new GastoRenglon(['gasto_id' => $gasto->id]), $renglon, $orden + 1, $userId);
        }
    }

    public function escribir(GastoRenglon $fila, RenglonAGuardar $renglon, int $orden, int $userId): GastoRenglon
    {
        $fila->fill([
            'orden' => $orden,
            'fecha' => $renglon->fecha,
            'concepto' => $renglon->concepto,
            'cantidad' => $renglon->cantidad,
            'precio_unitario' => $renglon->precioUnitario,
            'total' => $renglon->total,
            'obra_id' => $renglon->obraId,
            'rubro_id' => $renglon->rubroId,
            'material_id' => $this->materialId($renglon, $userId),
            'detalle' => $renglon->detalle,
        ])->save();

        return $fila;
    }

    private function materialId(RenglonAGuardar $renglon, int $userId): ?int
    {
        if ($renglon->materialNuevo === null || $renglon->obraId === null) {
            return $renglon->materialId;
        }

        return $this->materiales->crearFueraDePresupuesto(
            $renglon->obraId,
            $renglon->materialNuevo->nombre,
            $renglon->materialNuevo->unidad,
            $userId,
        );
    }
}
