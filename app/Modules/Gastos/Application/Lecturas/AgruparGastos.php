<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Correccion\FotoDeRenglon;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Gastos\Infrastructure\Consultas\CriterioDeRenglones;

/**
 * "Agrupar por: proveedor · obra · rubro", como sus tablas dinámicas (RN-GAS-33): cada grupo con su
 * subtotal y cantidad de comprobantes; un comprobante repartido aparece en cada grupo con la parte que le
 * toca; EPP es su propio grupo; del mayor subtotal al menor. Los anulados se ven pero no suman (RN-GAS-34).
 */
final readonly class AgruparGastos
{
    public const SIN_RUBRO = '(sin rubro)';

    public function __construct(private Rubros $rubros) {}

    /**
     * @param  list<GastoLeido>  $leidos
     * @return list<array{clave: string|int|null, nombre: string, subtotal: numeric-string, comprobantes: int, filas: list<array{leido: GastoLeido, parte: numeric-string}>}>
     */
    public function handle(string $por, array $leidos, CriterioDeRenglones $criterio): array
    {
        $grupos = [];

        foreach ($leidos as $leido) {
            $renglones = $criterio->filtraRenglones()
                ? array_values(array_filter($leido->renglones, $criterio->cumple(...)))
                : $leido->renglones;

            foreach ($this->partes($por, $leido, $renglones) as $clave => [$nombre, $parte]) {
                $grupos[$clave] ??= ['clave' => $this->claveVisible($por, $clave), 'nombre' => $nombre, 'subtotal' => '0.00', 'comprobantes' => 0, 'filas' => []];
                $grupos[$clave]['filas'][] = ['leido' => $leido, 'parte' => $parte];

                if (! $leido->gasto->anulado()) {
                    $grupos[$clave]['subtotal'] = bcadd($grupos[$clave]['subtotal'], $parte, TotalDeRenglon::ESCALA_IMPORTE);
                    $grupos[$clave]['comprobantes']++;
                }
            }
        }

        $lista = array_values($grupos);
        usort($lista, static fn (array $a, array $b): int => bccomp($b['subtotal'], $a['subtotal'], TotalDeRenglon::ESCALA_IMPORTE));

        return $lista;
    }

    /**
     * @param  list<GastoRenglon>  $renglones
     * @return array<string, array{0: string, 1: numeric-string}> clave interna => [nombre, parte]
     */
    private function partes(string $por, GastoLeido $leido, array $renglones): array
    {
        if ($por === FiltrosDeGastos::AGRUPAR_PROVEEDOR) {
            return ['p'.$leido->gasto->proveedor_id => [$leido->gasto->proveedor->nombre, LecturaDeGasto::suma($renglones)]];
        }

        $porClave = [];

        foreach ($renglones as $renglon) {
            [$clave, $nombre] = $por === FiltrosDeGastos::AGRUPAR_OBRA ? $this->obra($renglon) : $this->rubro($renglon);
            $porClave[$clave] ??= [$nombre, []];
            $porClave[$clave][1][] = $renglon;
        }

        return array_map(static fn (array $g): array => [$g[0], LecturaDeGasto::suma($g[1])], $porClave);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function obra(GastoRenglon $renglon): array
    {
        return $renglon->obra === null ? ['o', FotoDeRenglon::SIN_OBRA] : ['o'.$renglon->obra->id, $renglon->obra->nombre_trabajo];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rubro(GastoRenglon $renglon): array
    {
        $rubro = $this->rubros->porId($renglon->rubro_id);

        return $rubro === null ? ['r', self::SIN_RUBRO] : ['r'.$rubro->clave, $rubro->nombre];
    }

    private function claveVisible(string $por, string $clave): string|int|null
    {
        $resto = substr($clave, 1);

        return match (true) {
            $resto === '' => null,
            $por === FiltrosDeGastos::AGRUPAR_RUBRO => $resto,
            default => (int) $resto,
        };
    }
}
