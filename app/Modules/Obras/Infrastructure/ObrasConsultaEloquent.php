<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Obras\Contracts\MontoDeObra;
use App\Modules\Obras\Contracts\MontoDePresupuesto;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Infrastructure\Consultas\PorBusqueda;
use App\Modules\Obras\Infrastructure\Consultas\PresupuestadoDeObrasQuery;
use App\Shared\Domain\Importe;

/** Implementación de `ObrasConsulta` sobre las tablas del módulo. */
final readonly class ObrasConsultaEloquent implements ObrasConsulta
{
    public function __construct(private PresupuestadoDeObrasQuery $presupuestado) {}

    public function existe(int $obraId): bool
    {
        return Obra::query()->whereKey($obraId)->exists();
    }

    public function resumen(int $obraId): ?ObraResumen
    {
        $obra = Obra::query()->with('principal')->find($obraId);

        return $obra instanceof Obra ? self::aResumen($obra) : null;
    }

    public function esResponsable(int $userId, int $obraId): bool
    {
        return Obra::query()->whereKey($obraId)->where('responsable_id', $userId)->exists();
    }

    public function obrasDondeEsResponsable(int $userId): array
    {
        return array_values(Obra::query()->where('responsable_id', $userId)->orderBy('id')->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)->all());
    }

    public function opciones(?string $texto, int $limite): array
    {
        $query = Obra::query()->with('principal');

        if ($texto !== null && trim($texto) !== '') {
            (new PorBusqueda($texto))->aplicar($query);
        }

        return array_values($query
            ->orderByRaw('FIELD(estado, ?, ?, ?)', [ObraEstado::EnEjecucion->value, ObraEstado::Confirmada->value, ObraEstado::Terminada->value])
            ->orderBy('nombre_trabajo')
            ->orderBy('id')
            ->limit($limite)
            ->get()
            ->map(self::aResumen(...))
            ->all());
    }

    public function presupuestadoPorRubro(int $obraId): PresupuestadoDeObra
    {
        return $this->presupuestado->handle([$obraId])[$obraId];
    }

    public function presupuestadoDeObras(array $obraIds): array
    {
        return $this->presupuestado->handle(array_values(array_unique($obraIds)));
    }

    public function montoDeObra(int $obraId): ?MontoDeObra
    {
        $presupuestos = Presupuesto::query()->where('obra_id', $obraId)->orderByDesc('es_principal')->orderBy('id')->get(['numero', 'monto_obra']);
        $total = Importe::cero();
        $hayMonto = false;
        $porPresupuesto = [];

        foreach ($presupuestos as $presupuesto) {
            $monto = $presupuesto->monto_obra === null ? null : Importe::desde($presupuesto->monto_obra);
            $porPresupuesto[] = new MontoDePresupuesto($presupuesto->numero, $monto?->valor);

            if ($monto !== null) {
                $total = $total->sumar($monto);
                $hayMonto = true;
            }
        }

        return $hayMonto ? new MontoDeObra($total->valor, $porPresupuesto) : null;
    }

    public function obrasEnEjecucion(?array $soloEstas): array
    {
        if ($soloEstas === []) {
            return [];
        }

        return array_values(Obra::query()
            ->with('principal')
            ->where('estado', ObraEstado::EnEjecucion->value)
            ->when($soloEstas !== null, static fn ($query) => $query->whereKey($soloEstas))
            ->orderBy('id')
            ->get()
            ->map(self::aResumen(...))
            ->all());
    }

    private static function aResumen(Obra $obra): ObraResumen
    {
        return new ObraResumen(
            $obra->id,
            $obra->principal->numero ?? '',
            $obra->nombre_trabajo,
            $obra->cliente,
            $obra->estado->value,
            $obra->responsable_id,
        );
    }
}
