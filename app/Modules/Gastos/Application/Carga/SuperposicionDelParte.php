<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Shared\Domain\Importe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * RN-GAS-55 (DU-C02, como DU-G09): si en una obra del parte ya hay HORAS LIQUIDADAS cargadas como monto por
 * período cuyo período se superpone, avisa sin frenar, una línea por obra, para no contar dos veces. Se guarda
 * si se confirma (`confirmar_superposicion`).
 */
final readonly class SuperposicionDelParte
{
    public const CAMPO = 'confirmar_superposicion';

    public function __construct(
        private Rubros $rubros,
        private ObrasConsulta $obras,
    ) {}

    /**
     * @throws ValidationException 422 con una línea por obra
     */
    public function verificar(GastoAGuardar $gasto): bool
    {
        $obraIds = $gasto->obraIds();
        $rubroId = $this->rubros->idDe(Rubros::HORAS_LIQUIDADAS);

        if ($obraIds === [] || $rubroId === null || $gasto->periodoDesde === null || $gasto->periodoHasta === null) {
            return true;
        }

        $montos = Gasto::query()->with('renglones')
            ->where('origen', Origen::MontoPeriodo->value)->whereNull('anulado_at')
            ->whereHas('renglones', static fn (Builder $q) => $q->whereIn('obra_id', $obraIds)->where('rubro_id', $rubroId))
            ->where('periodo_desde', '<=', $gasto->periodoHasta)->where('periodo_hasta', '>=', $gasto->periodoDesde)
            ->orderBy('periodo_desde')->orderBy('id')->get();

        $porObra = [];

        foreach ($montos as $monto) {
            $obraId = $monto->renglones->first()?->obra_id;

            if ($obraId !== null) {
                $porObra[$obraId][] = 'del '.EtiquetaDocumento::periodo($monto->periodo_desde, $monto->periodo_hasta)
                    .' ('.Importe::desde(TotalDeRenglon::suma(array_values($monto->renglones->pluck('total')->all())))->legible().')';
            }
        }

        if ($porObra === []) {
            return true;
        }

        $lineas = [];

        foreach ($obraIds as $obraId) {
            if (isset($porObra[$obraId])) {
                $obra = $this->obras->resumen($obraId)?->nombreTrabajo;
                $lineas[] = "Ya hay HORAS LIQUIDADAS de {$obra} ".implode(' y ', $porObra[$obraId]).' cargadas como monto por período. ¿Cargar igual?';
            }
        }

        throw ValidationException::withMessages([self::CAMPO => $lineas]);
    }
}
