<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion\Support;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use Illuminate\Contracts\Container\Container;

/**
 * Lo que Gastos implementa (rendicion.md §12.4.1), en memoria y con las garantías del contrato: sin anulados ni
 * renglones sin obra (salvo `contarRenglonesPorMaterial`, que cuenta anulados), negativos incluidos, filtros
 * por rubro, fecha efectiva y material, orden y límite. Cuenta las llamadas para probar "una consulta".
 */
final class GastadoDeObrasFalso implements GastadoDeObras
{
    /** @var list<array{obraId: int|null, anulado: bool, renglon: RenglonImputado}> */
    private array $filas = [];

    public int $consultasDeTotales = 0;

    public int $consultasDeRenglones = 0;

    public static function registrar(Container $app): self
    {
        $falso = new self;
        $app->instance(GastadoDeObras::class, $falso);

        return $falso;
    }

    public function agregar(?int $obraId, RenglonImputado $renglon, bool $anulado = false): void
    {
        $this->filas[] = ['obraId' => $obraId, 'anulado' => $anulado, 'renglon' => $renglon];
    }

    /**
     * Anula el gasto (todos sus renglones) y devuelve las obras afectadas, como `GastoAnulado::obraIds`.
     *
     * @return list<int>
     */
    public function anular(int $gastoId): array
    {
        $obras = [];

        foreach ($this->filas as $i => $fila) {
            if ($fila['renglon']->gastoId === $gastoId) {
                $this->filas[$i]['anulado'] = true;
                if ($fila['obraId'] !== null) {
                    $obras[] = $fila['obraId'];
                }
            }
        }

        return array_values(array_unique($obras));
    }

    public function totalesPorRubro(array $obraIds): array
    {
        $this->consultasDeTotales++;
        $totales = [];

        foreach ($this->vigentes() as $fila) {
            if (in_array($fila['obraId'], $obraIds, true)) {
                $obra = (int) $fila['obraId'];
                $rubro = $fila['renglon']->rubroClave;
                $totales[$obra][$rubro] = bcadd($totales[$obra][$rubro] ?? '0', $this->numero($fila['renglon']->total), 2);
            }
        }

        return $totales;
    }

    public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array
    {
        $this->consultasDeRenglones++;
        $renglones = [];

        foreach ($this->vigentes() as $fila) {
            $r = $fila['renglon'];
            if ($fila['obraId'] === $obraId
                && ($filtro->rubroClaves === null || in_array($r->rubroClave, $filtro->rubroClaves, true))
                && ($filtro->desde === null || $r->fecha >= $filtro->desde)
                && ($filtro->hasta === null || $r->fecha <= $filtro->hasta)
                && (! $filtro->soloConMaterial || $r->materialId !== null)) {
                $renglones[] = $r;
            }
        }

        usort($renglones, function (RenglonImputado $a, RenglonImputado $b) use ($filtro): int {
            $signo = $filtro->dir === 'asc' ? 1 : -1;
            $comparacion = match ($filtro->orden) {
                FiltroRenglones::ORDEN_TOTAL => bccomp($this->numero($a->total), $this->numero($b->total), 2),
                FiltroRenglones::ORDEN_PROVEEDOR => strcmp((string) $a->proveedorNombre, (string) $b->proveedorNombre),
                default => strcmp($a->fecha, $b->fecha),
            };

            return $signo * $comparacion ?: $b->renglonId <=> $a->renglonId;
        });

        return $filtro->limite === null ? $renglones : array_slice($renglones, 0, $filtro->limite);
    }

    public function contarRenglonesPorMaterial(array $materialIds): array
    {
        $conteo = [];

        foreach ($this->filas as $fila) {
            $material = $fila['renglon']->materialId;
            if ($material !== null && in_array($material, $materialIds, true)) {
                $conteo[$material] = ($conteo[$material] ?? 0) + 1;
            }
        }

        return $conteo;
    }

    /**
     * @return list<array{obraId: int|null, anulado: bool, renglon: RenglonImputado}>
     */
    private function vigentes(): array
    {
        return array_values(array_filter($this->filas, static fn (array $f): bool => ! $f['anulado'] && $f['obraId'] !== null));
    }

    /**
     * @return numeric-string
     */
    private function numero(string $valor): string
    {
        return is_numeric($valor) ? $valor : '0';
    }
}
