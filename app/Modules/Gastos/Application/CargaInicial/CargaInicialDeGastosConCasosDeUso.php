<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\CargaInicial;

use App\Modules\Gastos\Application\Carga\CargarGastoAction;
use App\Modules\Gastos\Application\Carga\GuardarRenglones;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Contracts\CargaInicialDeGastos;
use App\Modules\Gastos\Contracts\CargaInicialVigente;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Shared\Actividad\RegistroActividad;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;

/**
 * `CargaInicialDeGastos` (RN-IMP-23, 29): cabecera `saldo_inicial` con el genérico, renglones validados por
 * `RenglonesIniciales`, la actividad `creado` con "gastado/retirado hasta el {corte}" y las equivalencias
 * (RN-IMP-17), y `GastoImputado` después del commit para que la rendición se entere (RN-REN-08).
 */
final readonly class CargaInicialDeGastosConCasosDeUso implements CargaInicialDeGastos
{
    public const GASTADO = 'gastado';

    public const RETIRADO = 'retirado';

    public function __construct(
        private Connection $db,
        private RenglonesIniciales $validar,
        private Rubros $rubros,
        private ProveedoresConsulta $proveedores,
        private GuardarRenglones $guardar,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    public function cargar(int $actorId, int $obraId, string $fechaCorte, string $tipo, array $renglones, array $historial = []): int
    {
        $aGuardar = $this->validar->handle($obraId, $fechaCorte, $tipo === self::RETIRADO, $renglones);

        return $this->db->transaction(function () use ($actorId, $obraId, $fechaCorte, $tipo, $aGuardar, $historial): int {
            $gasto = Gasto::query()->create([
                'tipo_documento' => Origen::SaldoInicial->tipoDocumento(),
                'origen' => Origen::SaldoInicial,
                'numero' => null,
                'fecha' => $fechaCorte,
                'proveedor_id' => $this->proveedores->generico()->valor,
                'cargado_por' => $actorId,
            ]);
            $this->guardar->crear($gasto, $aGuardar, $actorId);

            $corte = CarbonImmutable::parse($fechaCorte)->format('d/m/Y');
            $this->actividad->registrar($gasto, CargarGastoAction::ACCION, [
                'renglones' => ['antes' => null, 'despues' => count($aGuardar)],
                'carga_inicial' => ['antes' => null, 'despues' => "{$tipo} hasta el {$corte}"],
                ...($historial === [] ? [] : ['equivalencias' => ['antes' => null, 'despues' => implode(' ', $historial)]]),
            ]);
            $this->eventos->dispatch(new GastoImputado($gasto->id, [$obraId]));

            return $gasto->id;
        });
    }

    public function vigente(int $obraId, string $tipo): ?CargaInicialVigente
    {
        $retirado = $tipo === self::RETIRADO;
        $gasto = Gasto::query()
            ->with(['renglones', 'cargadoPor'])
            ->where('origen', Origen::SaldoInicial)
            ->whereNull('anulado_at')
            ->whereHas('renglones', static fn (Builder $q): Builder => $q->where('obra_id', $obraId)
                ->when($retirado, static fn (Builder $r): Builder => $r->whereNotNull('material_id'), static fn (Builder $r): Builder => $r->whereNull('material_id')))
            ->orderByDesc('id')
            ->first();

        if (! $gasto instanceof Gasto) {
            return null;
        }

        $porClave = [];

        foreach ($gasto->renglones as $renglon) {
            $clave = $retirado ? (string) $renglon->material_id : (string) $this->rubros->porId($renglon->rubro_id)?->clave;
            $porClave[$clave] = $retirado ? TotalDeRenglon::escala($renglon->cantidad, TotalDeRenglon::ESCALA_CANTIDAD) : TotalDeRenglon::escala($renglon->total);
        }

        return new CargaInicialVigente(
            $gasto->id,
            $gasto->fecha->toDateString(),
            $gasto->cargadoPor->nombre,
            FechaEmpresa::diaDe($gasto->created_at)->toDateString(),
            $porClave,
        );
    }

    public function renglonesHastaFecha(int $obraId, string $fecha): int
    {
        return GastoRenglon::query()
            ->where('obra_id', $obraId)
            ->where('fecha', '<=', $fecha)
            ->whereHas('gasto', static fn (Builder $q): Builder => $q->whereNull('anulado_at')->where('origen', '!=', Origen::SaldoInicial))
            ->count();
    }
}
