<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Resources;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;

/**
 * El parte de horas sin `gastos = editar` (RN-GAS-57): un renglón por obra con el concepto, las horas sumadas y el
 * total, sin empleado ni valor hora, para que horas y total no dejen sacar el valor hora de cada uno.
 */
final class RenglonesDelParte
{
    /**
     * @param  list<GastoRenglon>  $renglones
     * @param  callable(GastoRenglon): array<string, mixed>  $renglon  la forma de un renglón de `GastoDetalle`
     * @return list<array<string, mixed>>
     */
    public static function resumidos(array $renglones, callable $renglon): array
    {
        /** @var array<int, list<GastoRenglon>> $porObra */
        $porObra = [];

        foreach ($renglones as $r) {
            $porObra[$r->obra_id ?? 0][] = $r;
        }

        return array_values(array_map(static function (array $grupo) use ($renglon): array {
            $horas = '0';

            foreach ($grupo as $r) {
                $horas = bcadd($horas, $r->cantidad, TotalDeRenglon::ESCALA_CANTIDAD);
            }

            return [
                ...$renglon($grupo[0]),
                'id' => null,
                'cantidad' => $horas,
                'precio_unitario' => null,
                'total' => TotalDeRenglon::suma(array_map(static fn (GastoRenglon $r): string => $r->total, $grupo)),
                'material' => null,
                'detalle' => null,
                'empleado' => null,
                'resumido' => true,
            ];
        }, $porObra));
    }
}
