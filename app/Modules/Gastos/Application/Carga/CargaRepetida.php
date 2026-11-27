<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Domain\Importe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * Lo que no se carga dos veces (dentro de la transacción): la misma factura del mismo proveedor (RN-GAS-08,
 * 422; lo garantiza además el único `numero_vigente`), la amortización de la obra (RN-GAS-23, con la obra
 * bloqueada) y, avisando sin frenar, el remito repetido (RN-GAS-24) y el período superpuesto (RN-GAS-28).
 */
final readonly class CargaRepetida
{
    public function __construct(
        private ObrasConsulta $obras,
        private Rubros $rubros,
        private FacturaRepetida $facturas,
        private SuperposicionDelParte $parte,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 422
     */
    public function verificar(User $actor, GastoAGuardar $gasto, DatosGasto $datos, ?int $excluir = null): void
    {
        match ($gasto->origen) {
            Origen::Factura => $this->facturas->exigirQueNoExista($actor, $gasto->numero, $gasto->proveedorId, $excluir),
            Origen::RetiroRemito => $datos->confirmarRemitoRepetido || $this->remito($gasto, $excluir),
            Origen::Amortizacion => $this->amortizacion($gasto, $excluir),
            Origen::MontoPeriodo => $datos->confirmarSuperposicion || $this->superposicion($gasto, $excluir),
            Origen::ParteHoras => $datos->confirmarSuperposicion || $this->parte->verificar($gasto),
            default => null,
        };
    }

    private function remito(GastoAGuardar $gasto, ?int $excluir): bool
    {
        $existente = $this->vigentes(Origen::RetiroRemito, $excluir)->with('renglones.obra')
            ->where('proveedor_id', $gasto->proveedorId)->where('numero', $gasto->numero)->first();

        if ($existente === null) {
            return true;
        }

        $obra = $existente->renglones->first()?->obra?->nombre_trabajo;
        $donde = EtiquetaDocumento::diaYMes($existente->fecha).($obra === null ? '' : ", {$obra}");

        throw ValidationException::withMessages(['confirmar_remito_repetido' => ["Ese remito ya está cargado ({$donde}). ¿Cargarlo igual?"]]);
    }

    private function amortizacion(GastoAGuardar $gasto, ?int $excluir): void
    {
        $obraId = $gasto->obraIds()[0] ?? null;

        if ($obraId === null) {
            return;
        }

        // Dos cargas a la vez de la misma obra: la segunda espera y ve la primera (RN-GAS-50).
        Obra::query()->whereKey($obraId)->lockForUpdate()->first();

        $existente = $this->vigentes(Origen::Amortizacion, $excluir)->with('renglones')
            ->whereHas('renglones', static fn (Builder $q) => $q->where('obra_id', $obraId))->first();

        if ($existente !== null) {
            $importe = Importe::desde(TotalDeRenglon::suma(array_values($existente->renglones->pluck('total')->all())))->legible();

            throw Errores::yaCargado('renglones', "Esta obra ya tiene su amortización ({$importe}): corregila.", ['id' => $existente->id]);
        }
    }

    private function superposicion(GastoAGuardar $gasto, ?int $excluir): bool
    {
        $renglon = $gasto->renglones[0] ?? null;

        if ($renglon === null || $renglon->obraId === null || $gasto->periodoDesde === null || $gasto->periodoHasta === null) {
            return true;
        }

        // Las horas también pueden venir de un parte (DU-C02): se avisa en los dos sentidos para no contar dos veces.
        $origenes = [Origen::MontoPeriodo->value];
        if ($renglon->rubroId === $this->rubros->idDe(Rubros::HORAS_LIQUIDADAS)) {
            $origenes[] = Origen::ParteHoras->value;
        }
        $obraId = $renglon->obraId;

        $pisados = Gasto::query()->whereIn('origen', $origenes)->whereNull('anulado_at')
            ->when($excluir !== null, static fn (Builder $q) => $q->whereKeyNot($excluir))
            ->with(['renglones' => static fn ($q) => $q->where('obra_id', $obraId)])
            ->whereHas('renglones', static fn (Builder $q) => $q->where('obra_id', $obraId)->where('rubro_id', $renglon->rubroId))
            ->where('periodo_desde', '<=', $gasto->periodoHasta)->where('periodo_hasta', '>=', $gasto->periodoDesde)
            ->orderBy('periodo_desde')->orderBy('id')->get();

        if ($pisados->isEmpty()) {
            return true;
        }

        $rubro = $this->rubros->porId($renglon->rubroId)?->nombre;
        $obra = $this->obras->resumen($obraId)?->nombreTrabajo;
        $periodos = $pisados->map(static fn (Gasto $g): string => ($g->origen === Origen::ParteHoras ? 'en el parte de horas del ' : 'del ')
            .EtiquetaDocumento::periodo($g->periodo_desde, $g->periodo_hasta)
            .' ('.Importe::desde(TotalDeRenglon::suma(array_values($g->renglones->pluck('total')->all())))->legible().')')->implode(' y ');

        throw ValidationException::withMessages(['confirmar_superposicion' => ["Ya hay {$rubro} de {$obra} {$periodos}. ¿Cargar igual?"]]);
    }

    /**
     * @return Builder<Gasto>
     */
    private function vigentes(Origen $origen, ?int $excluir): Builder
    {
        return Gasto::query()->where('origen', $origen->value)->whereNull('anulado_at')
            ->when($excluir !== null, static fn (Builder $q) => $q->whereKeyNot($excluir));
    }
}
