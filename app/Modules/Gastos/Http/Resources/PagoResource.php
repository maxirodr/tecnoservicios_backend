<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Resources;

use App\Modules\Gastos\Domain\Models\Gasto;

/**
 * El estado de pago de una factura (DU-C03, gastos.md §12.2): `{estado: impaga|paga, pagado_at, pagado_por}`;
 * null si no es factura. Viaja solo con `pagos` ≥ ver (RN-GAS-61). Cargar con `pagadoPor`.
 */
final class PagoResource
{
    public const IMPAGA = 'impaga';

    public const PAGA = 'paga';

    /**
     * @return array{estado: string, pagado_at: string|null, pagado_por: array{id: int, nombre: string}|null}|null
     */
    public static function de(Gasto $gasto): ?array
    {
        if (! $gasto->tieneEstadoDePago()) {
            return null;
        }

        $por = $gasto->pagadoPor;

        return [
            'estado' => $gasto->pagado_at === null ? self::IMPAGA : self::PAGA,
            'pagado_at' => $gasto->pagado_at?->toDateString(),
            'pagado_por' => $gasto->pagado_at === null || $por === null ? null : ['id' => $por->id, 'nombre' => $por->nombre],
        ];
    }
}
