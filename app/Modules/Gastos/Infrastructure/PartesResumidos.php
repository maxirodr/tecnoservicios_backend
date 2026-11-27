<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\TotalDeRenglon;

/**
 * El parte de horas sin detalle por persona (RN-GAS-57, rendicion.md RN-REN-63): los renglones de un mismo parte
 * en la obra quedan en uno, en el lugar del primero, con las horas y el total sumados, sin empleado y sin valor
 * hora (`precioUnitario` "0.00", que Rendición no muestra). El resto de los renglones no cambia.
 */
final class PartesResumidos
{
    /**
     * @param  list<RenglonImputado>  $renglones  de una sola obra
     * @return list<RenglonImputado>
     */
    public static function de(array $renglones): array
    {
        /** @var array<int|string, RenglonImputado> $resultado */
        $resultado = [];

        foreach ($renglones as $indice => $renglon) {
            if ($renglon->origen !== Origen::ParteHoras->value) {
                $resultado[$indice] = $renglon;

                continue;
            }

            $clave = 'parte'.$renglon->gastoId;
            $previo = $resultado[$clave] ?? null;
            $resultado[$clave] = self::resumido($renglon, $previo);
        }

        return array_values($resultado);
    }

    private static function resumido(RenglonImputado $r, ?RenglonImputado $previo): RenglonImputado
    {
        $base = $previo ?? $r;

        return new RenglonImputado(
            $base->renglonId,
            $base->gastoId,
            $base->fecha,
            $base->origen,
            $base->tipoDocumento,
            $base->numero,
            $base->proveedorId,
            $base->proveedorNombre,
            $base->proveedorGenerico,
            $base->concepto,
            $previo === null ? $r->cantidad : self::suma($previo->cantidad, $r->cantidad, TotalDeRenglon::ESCALA_CANTIDAD),
            TotalDeRenglon::escala('0'),
            $previo === null ? $r->total : self::suma($previo->total, $r->total, TotalDeRenglon::ESCALA_IMPORTE),
            $base->rubroClave,
            null,
            $base->periodoDesde,
            $base->periodoHasta,
            $base->rendidoPor,
            null,
            true,
        );
    }

    private static function suma(string $a, string $b, int $escala): string
    {
        return bcadd(TotalDeRenglon::escala($a, $escala), TotalDeRenglon::escala($b, $escala), $escala);
    }
}
