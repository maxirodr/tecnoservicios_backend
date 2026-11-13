<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Historial;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Montos\CargarMontoObraAction;
use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\Actividad;
use App\Shared\Actividad\HistorialActividad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * F-20 / F-14 (W-23): historial de la obra, el más nuevo arriba, con lo registrado en la obra, sus
 * presupuestos y sus documentos (incluidos los adjuntos). Las líneas de montos por rubro y de monto
 * de la obra solo las ve quien tiene esas funciones (RN-OBR-28): se filtran en la consulta.
 */
final readonly class HistorialObraQuery
{
    public function __construct(
        private AccesoObra $acceso,
        private DescripcionActividad $descripcion,
    ) {}

    /**
     * @return LengthAwarePaginator<int, EntradaHistorial>
     */
    public function handle(User $user, Obra $obra, int $porPagina = HistorialActividad::POR_PAGINA): LengthAwarePaginator
    {
        $presupuestos = Presupuesto::query()->where('obra_id', $obra->id)->get(['id', 'numero', 'revision', 'es_principal'])->keyBy('id');
        $documentos = ObraDocumento::query()->where('obra_id', $obra->id)->get(['id', 'tipo', 'nombre'])->keyBy('id');
        $ocultas = [
            ...($this->acceso->verMontos($user, $obra) ? [] : [CargarMontosAction::ACCION]),
            ...($this->acceso->verMontoObra($user, $obra) ? [] : [CargarMontoObraAction::ACCION]),
        ];

        return Actividad::query()
            ->where(function (Builder $q) use ($obra, $presupuestos, $documentos): void {
                $q->where(fn (Builder $s) => $s->where('sujeto_type', Obra::MORPH)->where('sujeto_id', $obra->id))
                    ->orWhere(fn (Builder $s) => $s->where('sujeto_type', Presupuesto::MORPH)->whereIn('sujeto_id', $presupuestos->keys()->all()))
                    ->orWhere(fn (Builder $s) => $s->where('sujeto_type', ObraDocumento::MORPH)->whereIn('sujeto_id', $documentos->keys()->all()));
            })
            ->whereNotIn('accion', $ocultas)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->through(fn (Actividad $a): EntradaHistorial => $this->descripcion->entrada($a, $presupuestos->all(), $documentos->all()));
    }
}
