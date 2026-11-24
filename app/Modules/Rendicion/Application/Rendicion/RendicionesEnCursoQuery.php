<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Calculo\Porcentaje;
use App\Modules\Rendicion\Domain\Calculo\TotalDeObra;

/**
 * F-53 (W-12): las obras En ejecución que la persona puede ver (RN-REN-38…41, 55), de mayor a menor % consumido;
 * las sin presupuesto al final por N.º, y a igual % por N.º. El total suma las filas que se ven.
 */
final readonly class RendicionesEnCursoQuery
{
    public function __construct(
        private ObrasConsulta $obras,
        private RendicionesDeVariasObras $rendiciones,
    ) {}

    /**
     * @param  list<int>|null  $soloEstas  null = todas (alcance "todas")
     * @return array{filas: list<array{obra: ObraResumen, total: TotalDeObra}>, total: array{presupuestado: numeric-string, gastado: numeric-string, porcentaje: numeric-string|null, rubros_pasados: int|null}}
     */
    public function handle(?array $soloEstas): array
    {
        $obras = $this->obras->obrasEnEjecucion($soloEstas);
        $resumenes = $this->rendiciones->de(array_map(static fn (ObraResumen $o): int => $o->id, $obras));
        $filas = array_map(static fn (ObraResumen $o): array => ['obra' => $o, 'total' => $resumenes[$o->id]->total], $obras);

        usort($filas, static function (array $a, array $b): int {
            $pa = $a['total']->porcentaje;
            $pb = $b['total']->porcentaje;

            if ($pa === null || $pb === null) {
                return ($pa === null) <=> ($pb === null) ?: strnatcmp($a['obra']->numero, $b['obra']->numero);
            }

            return bccomp($pb, $pa, Decimal::IMPORTE) ?: strnatcmp($a['obra']->numero, $b['obra']->numero);
        });

        return ['filas' => $filas, 'total' => self::total($filas)];
    }

    /**
     * RN-REN-40: Σ presupuestado, Σ gastado, % = Σ gastado ÷ Σ presupuestado, Σ rubros pasados (los "—" no suman).
     *
     * @param  list<array{obra: ObraResumen, total: TotalDeObra}>  $filas
     * @return array{presupuestado: numeric-string, gastado: numeric-string, porcentaje: numeric-string|null, rubros_pasados: int|null}
     */
    private static function total(array $filas): array
    {
        $totales = array_map(static fn (array $f): TotalDeObra => $f['total'], $filas);
        $presupuestado = Decimal::suma(array_map(static fn (TotalDeObra $t): string => $t->presupuestado ?? '0', $totales));
        $gastado = Decimal::suma(array_map(static fn (TotalDeObra $t): string => $t->gastado, $totales));
        $pasados = array_filter(array_map(static fn (TotalDeObra $t): ?int => $t->rubrosPasados, $totales), static fn (?int $n): bool => $n !== null);

        return [
            'presupuestado' => $presupuestado,
            'gastado' => $gastado,
            'porcentaje' => Porcentaje::de($gastado, $presupuestado),
            'rubros_pasados' => $pasados === [] ? null : array_sum($pasados),
        ];
    }
}
