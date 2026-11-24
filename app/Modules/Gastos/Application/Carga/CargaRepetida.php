<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\TotalDeRenglon;
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
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 422
     */
    public function verificar(GastoAGuardar $gasto, DatosGasto $datos, ?int $excluir = null): void
    {
        match ($gasto->origen) {
            Origen::Factura => $this->factura($gasto, $excluir),
            Origen::RetiroRemito => $datos->confirmarRemitoRepetido || $this->remito($gasto, $excluir),
            Origen::Amortizacion => $this->amortizacion($gasto, $excluir),
            Origen::MontoPeriodo => $datos->confirmarSuperposicion || $this->superposicion($gasto, $excluir),
            default => null,
        };
    }

    public function facturaExistente(int $proveedorId, string $numero, ?int $excluir = null): ?Gasto
    {
        return Gasto::query()->with(['cargadoPor', 'proveedor'])
            ->where('proveedor_id', $proveedorId)
            ->where('numero_vigente', $numero)
            ->when($excluir !== null, static fn (Builder $q) => $q->whereKeyNot($excluir))
            ->first();
    }

    /** "Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 17-oct)." (quién y cuándo la cargó). */
    public static function mensajeDeFacturaCargada(Gasto $existente): string
    {
        $cuando = EtiquetaDocumento::diaYMes(FechaEmpresa::diaDe($existente->created_at));

        return "Esa factura ya está cargada: {$existente->numero} de {$existente->proveedor->nombre} ({$existente->cargadoPor->nombre}, {$cuando}).";
    }

    public static function facturaCargada(Gasto $existente): HttpResponseException
    {
        $quien = $existente->cargadoPor->nombre;

        return Errores::yaCargado('numero', self::mensajeDeFacturaCargada($existente), [
            'id' => $existente->id,
            'numero' => $existente->numero,
            'fecha' => $existente->fecha->toDateString(),
            'cargado_por' => ['id' => $existente->cargado_por, 'nombre' => $quien],
        ]);
    }

    private function factura(GastoAGuardar $gasto, ?int $excluir): void
    {
        $existente = $gasto->numero === null ? null : $this->facturaExistente($gasto->proveedorId, $gasto->numero, $excluir);

        if ($existente !== null) {
            throw self::facturaCargada($existente);
        }
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

        $pisados = $this->vigentes(Origen::MontoPeriodo, $excluir)->with('renglones')
            ->whereHas('renglones', static fn (Builder $q) => $q->where('obra_id', $renglon->obraId)->where('rubro_id', $renglon->rubroId))
            ->where('periodo_desde', '<=', $gasto->periodoHasta)->where('periodo_hasta', '>=', $gasto->periodoDesde)
            ->orderBy('periodo_desde')->get();

        if ($pisados->isEmpty()) {
            return true;
        }

        $rubro = $this->rubros->porId($renglon->rubroId)?->nombre;
        $obra = $this->obras->resumen($renglon->obraId)?->nombreTrabajo;
        $periodos = $pisados->map(static fn (Gasto $g): string => 'del '.EtiquetaDocumento::periodo($g->periodo_desde, $g->periodo_hasta)
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
