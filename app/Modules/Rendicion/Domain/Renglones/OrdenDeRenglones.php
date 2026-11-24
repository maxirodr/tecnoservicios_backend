<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\TextoComparable;

/**
 * Orden del detalle de un rubro (RN-REN-31) y de los últimos gastos (RN-REN-28): por fecha efectiva (por
 * defecto, la más nueva primero), proveedor (A→Z) o total (mayor primero); a igual valor, el cargado último
 * primero. En el orden por proveedor, los del genérico ("—") van al final.
 */
final class OrdenDeRenglones
{
    public const DIRECCIONES = ['asc', 'desc'];

    public const ORDENES = [FiltroRenglones::ORDEN_FECHA, FiltroRenglones::ORDEN_PROVEEDOR, FiltroRenglones::ORDEN_TOTAL];

    /**
     * La dirección que se usa si no se pide otra.
     *
     * @return 'asc'|'desc'
     */
    public static function direccionPorDefecto(string $orden): string
    {
        return $orden === FiltroRenglones::ORDEN_PROVEEDOR ? 'asc' : 'desc';
    }

    /**
     * @param  list<RenglonImputado>  $renglones
     * @return list<RenglonImputado>
     */
    public static function ordenar(array $renglones, string $orden, string $dir): array
    {
        $signo = $dir === 'asc' ? 1 : -1;

        usort($renglones, static function (RenglonImputado $a, RenglonImputado $b) use ($orden, $signo): int {
            $comparacion = match ($orden) {
                FiltroRenglones::ORDEN_PROVEEDOR => self::porProveedor($a, $b, $signo),
                FiltroRenglones::ORDEN_TOTAL => $signo * Decimal::comparar($a->total, $b->total),
                default => $signo * strcmp($a->fecha, $b->fecha),
            };

            return $comparacion !== 0 ? $comparacion : $b->renglonId <=> $a->renglonId;
        });

        return $renglones;
    }

    private static function porProveedor(RenglonImputado $a, RenglonImputado $b, int $signo): int
    {
        $nombreA = DocumentoDelRenglon::proveedor($a);
        $nombreB = DocumentoDelRenglon::proveedor($b);

        if ($nombreA === null || $nombreB === null) {
            return ($nombreA === null) <=> ($nombreB === null);
        }

        return $signo * strcmp(TextoComparable::de($nombreA), TextoComparable::de($nombreB));
    }
}
