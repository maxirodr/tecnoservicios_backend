<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use Illuminate\Database\Query\Builder;

/**
 * Lo que Rendicion lee de Gastos (rendicion.md §12.4.1, gastos.md §12.5): sale solo de `gasto_renglones`
 * (ADR-007). Sin anulados ni "sin obra" (salvo `contarRenglonesPorMaterial`, que cuenta anulados), el rubro
 * como se imputó (EPP aparte), importes con signo y escala fija, la fecha efectiva del renglón. Una consulta
 * por llamada, sin N+1.
 */
final readonly class GastadoDeObrasEloquent implements GastadoDeObras
{
    public function __construct(private Rubros $rubros) {}

    public function totalesPorRubro(array $obraIds): array
    {
        if ($obraIds === []) {
            return [];
        }

        $filas = $this->vigentes()
            ->whereIn('gasto_renglones.obra_id', $obraIds)
            ->groupBy('gasto_renglones.obra_id', 'gasto_renglones.rubro_id')
            ->orderBy('gasto_renglones.obra_id')->orderBy('gasto_renglones.rubro_id')
            ->selectRaw('gasto_renglones.obra_id AS obra_id, gasto_renglones.rubro_id AS rubro_id, SUM(gasto_renglones.total) AS total')
            ->get();

        // Obra sin renglones: [] (rendicion.md §12.4.1).
        $totales = array_fill_keys($obraIds, []);

        foreach ($filas as $fila) {
            $clave = $this->rubros->porId((int) $fila->rubro_id)?->clave;

            if ($clave !== null) {
                $totales[(int) $fila->obra_id][$clave] = TotalDeRenglon::escala($fila->total);
            }
        }

        return $totales;
    }

    public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array
    {
        $query = $this->vigentes()
            ->join('proveedores', 'proveedores.id', '=', 'gastos.proveedor_id')
            ->where('gasto_renglones.obra_id', $obraId)
            ->select([
                'gasto_renglones.id', 'gasto_renglones.gasto_id', 'gasto_renglones.fecha', 'gasto_renglones.concepto',
                'gasto_renglones.cantidad', 'gasto_renglones.precio_unitario', 'gasto_renglones.total', 'gasto_renglones.rubro_id',
                'gasto_renglones.material_id', 'gastos.origen', 'gastos.tipo_documento', 'gastos.numero', 'gastos.proveedor_id',
                'gastos.periodo_desde', 'gastos.periodo_hasta', 'gastos.rendido_por',
                'proveedores.nombre AS proveedor_nombre', 'proveedores.es_generico AS proveedor_generico',
            ]);

        $this->filtrar($query, $filtro);

        return array_values($query->get()->map(fn (object $f): RenglonImputado => new RenglonImputado(
            (int) $f->id,
            (int) $f->gasto_id,
            (string) $f->fecha,
            (string) $f->origen,
            (string) $f->tipo_documento,
            $f->numero === null ? null : (string) $f->numero,
            (int) $f->proveedor_id,
            (string) $f->proveedor_nombre,
            (bool) $f->proveedor_generico,
            (string) $f->concepto,
            TotalDeRenglon::escala($f->cantidad, TotalDeRenglon::ESCALA_CANTIDAD),
            TotalDeRenglon::escala($f->precio_unitario),
            TotalDeRenglon::escala($f->total),
            (string) $this->rubros->porId($f->rubro_id === null ? null : (int) $f->rubro_id)?->clave,
            $f->material_id === null ? null : (int) $f->material_id,
            $f->periodo_desde === null ? null : (string) $f->periodo_desde,
            $f->periodo_hasta === null ? null : (string) $f->periodo_hasta,
            $f->rendido_por === null ? null : (string) $f->rendido_por,
        ))->all());
    }

    public function contarRenglonesPorMaterial(array $materialIds): array
    {
        if ($materialIds === []) {
            return [];
        }

        return GastoRenglon::query()->toBase()
            ->whereIn('material_id', $materialIds)
            ->groupBy('material_id')
            ->selectRaw('material_id, COUNT(*) AS renglones')
            ->pluck('renglones', 'material_id')
            ->mapWithKeys(static fn (mixed $cantidad, mixed $id): array => [(int) $id => (int) $cantidad])
            ->all();
    }

    /** Renglones con obra de gastos no anulados. */
    private function vigentes(): Builder
    {
        return GastoRenglon::query()->toBase()
            ->join('gastos', 'gastos.id', '=', 'gasto_renglones.gasto_id')
            ->whereNull('gastos.anulado_at')
            ->whereNotNull('gasto_renglones.obra_id');
    }

    private function filtrar(Builder $query, FiltroRenglones $filtro): void
    {
        if ($filtro->rubroClaves !== null) {
            $query->whereIn('gasto_renglones.rubro_id', $this->rubros->idsDe($filtro->rubroClaves) ?: [0]);
        }

        if ($filtro->desde !== null) {
            $query->where('gasto_renglones.fecha', '>=', $filtro->desde);
        }

        if ($filtro->hasta !== null) {
            $query->where('gasto_renglones.fecha', '<=', $filtro->hasta);
        }

        if ($filtro->soloConMaterial) {
            $query->whereNotNull('gasto_renglones.material_id');
        }

        $dir = $filtro->dir === 'asc' ? 'asc' : 'desc';
        $columna = match ($filtro->orden) {
            FiltroRenglones::ORDEN_PROVEEDOR => 'proveedores.nombre',
            FiltroRenglones::ORDEN_TOTAL => 'gasto_renglones.total',
            default => 'gasto_renglones.fecha',
        };

        $query->orderBy($columna, $dir)->orderBy('gasto_renglones.fecha', $dir)->orderBy('gasto_renglones.id', $dir);

        if ($filtro->limite !== null) {
            $query->limit($filtro->limite);
        }
    }
}
