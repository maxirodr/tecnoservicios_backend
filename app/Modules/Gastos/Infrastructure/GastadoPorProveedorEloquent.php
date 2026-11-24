<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure;

use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Proveedores\Application\Ports\AlcanceObras;
use App\Modules\Proveedores\Application\Ports\FilaGastado;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Application\Ports\ObraGastada;
use App\Modules\Proveedores\Contracts\ProveedorId;

/**
 * F-57 / HU-084 · W-14 "Gastado por obra" (RN-GAS-43): **una consulta** sobre los renglones no anulados del
 * proveedor, agrupada por obra (null = "(sin obra)"), con la cantidad de renglones y el importe (las
 * devoluciones restan). Más renglones primero, después más importe; "(sin obra)" al final. Con "solo sus
 * obras", solo esas y sin la fila "(sin obra)" (RN-PRV-16).
 */
final readonly class GastadoPorProveedorEloquent implements GastadoPorProveedor
{
    public function __construct(private ObrasConsulta $obras) {}

    public function porObra(ProveedorId $proveedor, AlcanceObras $alcance): GastadoPorObra
    {
        $query = GastoRenglon::query()->toBase()
            ->join('gastos', 'gastos.id', '=', 'gasto_renglones.gasto_id')
            ->leftJoin('obras', 'obras.id', '=', 'gasto_renglones.obra_id')
            ->leftJoin('presupuestos', static fn ($join) => $join->on('presupuestos.obra_id', '=', 'gasto_renglones.obra_id')->where('presupuestos.es_principal', true))
            ->where('gastos.proveedor_id', $proveedor->valor)
            ->whereNull('gastos.anulado_at')
            ->groupBy('gasto_renglones.obra_id', 'obras.nombre_trabajo', 'presupuestos.numero')
            ->selectRaw('gasto_renglones.obra_id AS obra_id, obras.nombre_trabajo AS nombre_trabajo, presupuestos.numero AS numero, COUNT(*) AS renglones, SUM(gasto_renglones.total) AS importe')
            ->orderByRaw('gasto_renglones.obra_id IS NULL')
            ->orderByDesc('renglones')
            ->orderByDesc('importe');

        if ($alcance->userId !== null) {
            $query->whereIn('gasto_renglones.obra_id', $this->obras->obrasDondeEsResponsable($alcance->userId) ?: [0]);
        }

        return new GastadoPorObra(array_values($query->get()->map(static fn (object $f): FilaGastado => new FilaGastado(
            $f->obra_id === null ? null : new ObraGastada((int) $f->obra_id, (string) ($f->numero ?? ''), (string) $f->nombre_trabajo),
            (int) $f->renglones,
            TotalDeRenglon::escala($f->importe),
        ))->all()));
    }
}
