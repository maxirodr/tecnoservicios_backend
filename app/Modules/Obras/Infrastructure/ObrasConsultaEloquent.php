<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Shared\Domain\Importe;

/** Implementación de `ObrasConsulta` sobre las tablas del módulo. */
final class ObrasConsultaEloquent implements ObrasConsulta
{
    public function existe(int $obraId): bool
    {
        return Obra::query()->whereKey($obraId)->exists();
    }

    public function resumen(int $obraId): ?ObraResumen
    {
        $obra = Obra::query()->with('principal')->find($obraId);

        if (! $obra instanceof Obra) {
            return null;
        }

        return new ObraResumen(
            $obra->id,
            $obra->principal->numero ?? '',
            $obra->nombre_trabajo,
            $obra->cliente,
            $obra->estado->value,
            $obra->responsable_id,
        );
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

    public function presupuestadoPorRubro(int $obraId): PresupuestadoDeObra
    {
        $filas = PresupuestoMonto::query()
            ->join('presupuestos', 'presupuestos.id', '=', 'presupuesto_montos.presupuesto_id')
            ->leftJoin('rubros', 'rubros.id', '=', 'presupuesto_montos.rubro_id')
            ->where('presupuestos.obra_id', $obraId)
            ->groupBy('rubros.clave')
            ->selectRaw('rubros.clave as clave, SUM(presupuesto_montos.monto) as monto')
            ->toBase()
            ->get();

        $porRubro = [];
        $sinDesglose = Importe::cero();
        $total = Importe::cero();

        foreach ($filas as $fila) {
            $monto = Importe::desde((string) $fila->monto);
            $total = $total->sumar($monto);

            if ($fila->clave === null) {
                $sinDesglose = $monto;

                continue;
            }

            $porRubro[(string) $fila->clave] = $monto->valor;
        }

        return new PresupuestadoDeObra($porRubro, $sinDesglose->valor, $total->valor);
    }
}
