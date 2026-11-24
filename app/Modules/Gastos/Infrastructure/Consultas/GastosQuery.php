<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Gastos cargados (W-33, RN-GAS-30…34): los filtros de cabecera sobre `gastos` y los de renglón con un
 * `EXISTS` sobre `gasto_renglones`; el total de lo filtrado sobre **todo** lo filtrado (no la página),
 * sumando solo los renglones que cumplen, sin anulados.
 */
final class GastosQuery
{
    /**
     * @param  list<FiltroGastos>  $cabecera
     * @return Builder<Gasto>
     */
    public static function filtrados(array $cabecera, CriterioDeRenglones $criterio): Builder
    {
        $query = Gasto::query();

        foreach ($cabecera as $filtro) {
            $filtro->aplicar($query->getQuery());
        }

        if (! $criterio->vacio()) {
            $query->whereExists(static function (QueryBuilder $renglones) use ($criterio): void {
                $renglones->selectRaw('1')->from('gasto_renglones')->whereColumn('gasto_renglones.gasto_id', 'gastos.id');
                $criterio->aplicar($renglones);
            });
        }

        return $query;
    }

    /**
     * Fecha del comprobante, la más nueva primero; a igual fecha, el cargado más tarde (RN-GAS-32).
     *
     * @param  Builder<Gasto>  $query
     * @return Builder<Gasto>
     */
    public static function ordenados(Builder $query): Builder
    {
        return $query->orderByDesc('gastos.fecha')->orderByDesc('gastos.created_at')->orderByDesc('gastos.id');
    }

    /**
     * @param  list<FiltroGastos>  $cabecera
     * @return array{total: numeric-string, comprobantes: int, anulados: int}
     */
    public static function totales(array $cabecera, CriterioDeRenglones $criterio): array
    {
        $suma = GastoRenglon::query()->toBase()
            ->join('gastos', 'gastos.id', '=', 'gasto_renglones.gasto_id')
            ->whereNull('gastos.anulado_at');

        foreach ($cabecera as $filtro) {
            $filtro->aplicar($suma);
        }

        $criterio->aplicar($suma);
        $total = $suma->sum('gasto_renglones.total');

        $cuenta = self::filtrados($cabecera, $criterio)->toBase()
            ->selectRaw('COUNT(*) AS comprobantes, COALESCE(SUM(gastos.anulado_at IS NOT NULL), 0) AS anulados')
            ->first();

        return [
            'total' => TotalDeRenglon::escala($total),
            'comprobantes' => (int) ($cuenta->comprobantes ?? 0),
            'anulados' => (int) ($cuenta->anulados ?? 0),
        ];
    }
}
