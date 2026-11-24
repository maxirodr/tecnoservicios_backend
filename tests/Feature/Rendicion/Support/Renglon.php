<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion\Support;

use App\Modules\Gastos\Contracts\RenglonImputado;

/** Arma un `RenglonImputado` ficticio con lo mínimo; el resto, como lo dejaría Gastos. */
final class Renglon
{
    public const GENERICO_ID = 1;

    /**
     * @param  array{proveedor?: array{0: int, 1: string}|null, numero?: string|null, material?: int|null, desde?: string|null, hasta?: string|null, cantidad?: string, precio?: string}  $extra
     */
    public static function de(int $id, int $gastoId, string $fecha, string $origen, string $concepto, string $total, string $rubro, array $extra = []): RenglonImputado
    {
        $proveedor = $extra['proveedor'] ?? null;

        return new RenglonImputado(
            $id,
            $gastoId,
            $fecha,
            $origen,
            $origen === 'factura' ? 'factura' : 'sin_documento',
            $extra['numero'] ?? null,
            $proveedor === null ? self::GENERICO_ID : $proveedor[0],
            $proveedor === null ? 'Sin proveedor (ejemplo)' : $proveedor[1],
            $proveedor === null,
            $concepto,
            $extra['cantidad'] ?? '1.000',
            $extra['precio'] ?? $total,
            $total,
            $rubro,
            $extra['material'] ?? null,
            $extra['desde'] ?? null,
            $extra['hasta'] ?? null,
            null,
        );
    }
}
