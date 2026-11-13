<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de obras con filtros componibles (F-15, F-03): contadores por pestaña y filas de una
 * pestaña con lo que la fila necesita en pocas consultas (principal, responsable, adicionales).
 */
final class ObrasQuery
{
    /**
     * @param  list<FiltroObras>  $filtros
     * @return Builder<Obra>
     */
    public function base(array $filtros): Builder
    {
        $query = Obra::query();

        foreach ($filtros as $filtro) {
            $filtro->aplicar($query);
        }

        return $query;
    }

    /**
     * RN-OBR-06: los contadores muestran lo que queda con la búsqueda y el filtro aplicados.
     *
     * @param  list<FiltroObras>  $filtros
     * @return array<string, int>
     */
    public function contadores(array $filtros): array
    {
        $cantidades = $this->base($filtros)->toBase()
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado');

        $contadores = [];

        foreach (ObraEstado::enOrdenDePestanas() as $estado) {
            $contadores[$estado->value] = (int) ($cantidades[$estado->value] ?? 0);
        }

        return $contadores;
    }

    /**
     * Ids de la pestaña con el N.º del principal, en el orden de alta/terminación de RN-OBR-07
     * (En ejecución se reordena después por % gastado, que viene de otro módulo).
     *
     * @param  list<FiltroObras>  $filtros
     * @return array<int, string> obraId => N.º del principal
     */
    public function idsOrdenados(array $filtros, ObraEstado $estado): array
    {
        $query = $this->base([...$filtros, new PorEstado($estado)])
            ->select('obras.id')
            ->addSelect(['numero_principal' => Presupuesto::query()->select('numero')
                ->whereColumn('presupuestos.obra_id', 'obras.id')
                ->where('es_principal', true)
                ->limit(1)]);

        match ($estado) {
            ObraEstado::Confirmada => $query->orderByDesc('obras.created_at')->orderByDesc('obras.id'),
            ObraEstado::Terminada => $query->orderByDesc('obras.terminada_at')->orderByDesc('obras.id'),
            ObraEstado::EnEjecucion => $query->orderBy('numero_principal')->orderBy('obras.id'),
        };

        /** @var array<int, string> $ids */
        $ids = $query->toBase()->pluck('numero_principal', 'id')->map(static fn (mixed $n): string => (string) $n)->all();

        return $ids;
    }

    /**
     * Filas para mostrar, en el orden de `$ids`.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Obra>
     */
    public function filas(array $ids): Collection
    {
        $obras = Obra::query()
            ->whereKey($ids)
            ->with(['principal', 'responsable'])
            ->withCount(['presupuestos as adicionales_count' => fn (Builder $q) => $q->where('es_principal', false)])
            ->get()
            ->keyBy('id');

        return new Collection(array_values(array_filter(array_map(static fn (int $id): ?Obra => $obras->get($id), $ids))));
    }

    /**
     * RN-OBR-17: clientes ya usados en otras obras, distintos, para sugerir al escribir.
     *
     * @param  list<FiltroObras>  $filtros
     * @return list<string>
     */
    public function clientes(array $filtros, ?string $buscar, int $limite): array
    {
        $query = $this->base($filtros)->toBase()->select('cliente')->distinct()->orderBy('cliente')->limit($limite);

        if ($buscar !== null && trim($buscar) !== '') {
            $query->where('cliente', 'like', '%'.addcslashes(trim($buscar), '\\%_').'%');
        }

        return array_values($query->pluck('cliente')->map(static fn (mixed $c): string => (string) $c)->all());
    }
}
