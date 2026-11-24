<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Shared\Http\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * W-46 "{N.º} · {obra} · renglones sin documento" (RN-GAS-22): lo ya cargado de la obra en cada sección
 * (no anulado), el último proveedor de un retiro (RN-GAS-24) y qué sección puede cargar quien mira (§8.1,
 * resuelta DU-R05). La ven `rendicion` o `control_cantidad` ≥ ver, en sus obras.
 */
final readonly class RenglonesSinDocumentoQuery
{
    public function __construct(
        private AccesoGasto $acceso,
        private ObrasConsulta $obras,
        private LecturaDeGasto $lectura,
    ) {}

    /**
     * @return array{obra: ObraResumen, amortizacion: GastoLeido|null, retiros: list<GastoLeido>, devoluciones: list<GastoLeido>, periodos: list<GastoLeido>, ultimo_proveedor_retiro: array{id: int, nombre: string}|null, puede: array<string, bool>}
     */
    public function handle(User $user, int $obraId): array
    {
        $obra = $this->obras->resumen($obraId) ?? throw new HttpResponseException(ApiResponse::error('Obra no encontrada.', 404));
        $ve = $this->alcanza($user, ClaveFuncion::Rendicion, Nivel::Ver, $obraId) || $this->alcanza($user, ClaveFuncion::ControlCantidad, Nivel::Ver, $obraId);
        BuscarGasto::exigir($ve);

        $cargados = Gasto::query()->with(LecturaDeGasto::DETALLE)
            ->whereIn('origen', [Origen::Amortizacion->value, Origen::RetiroRemito->value, Origen::Devolucion->value, Origen::MontoPeriodo->value])
            ->whereNull('anulado_at')
            ->whereHas('renglones', static fn (Builder $q) => $q->where('obra_id', $obraId))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get();

        $de = fn (Origen $origen): array => array_values($cargados->filter(static fn (Gasto $g): bool => $g->origen === $origen)
            ->map(fn (Gasto $g): GastoLeido => $this->lectura->de($user, $g, completo: true))->all());

        $rendicion = $this->alcanza($user, ClaveFuncion::Rendicion, Nivel::Editar, $obraId);

        return [
            'obra' => $obra,
            'amortizacion' => $de(Origen::Amortizacion)[0] ?? null,
            'retiros' => $de(Origen::RetiroRemito),
            'devoluciones' => $de(Origen::Devolucion),
            'periodos' => $de(Origen::MontoPeriodo),
            'ultimo_proveedor_retiro' => $this->ultimoProveedorDeRetiro(),
            'puede' => [
                'amortizacion' => $rendicion,
                'retiros' => $rendicion || $this->alcanza($user, ClaveFuncion::ControlCantidad, Nivel::Editar, $obraId),
                'devoluciones' => $rendicion,
                'periodos' => $rendicion,
            ],
        ];
    }

    private function alcanza(User $user, ClaveFuncion $funcion, Nivel $nivel, int $obraId): bool
    {
        if (! $this->acceso->puede($user, $funcion, $nivel)) {
            return false;
        }

        $obras = $this->acceso->obrasDe($user, $funcion);

        return $obras === null || in_array($obraId, $obras, true);
    }

    /**
     * @return array{id: int, nombre: string}|null
     */
    private function ultimoProveedorDeRetiro(): ?array
    {
        $ultimo = Gasto::query()->with('proveedor')->where('origen', Origen::RetiroRemito->value)->whereNull('anulado_at')
            ->latest('created_at')->latest('id')->first();

        return $ultimo === null ? null : ['id' => $ultimo->proveedor->id, 'nombre' => $ultimo->proveedor->nombre];
    }
}
