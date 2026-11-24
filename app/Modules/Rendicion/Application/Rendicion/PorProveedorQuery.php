<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Renglones\AgrupacionPorProveedor;
use App\Modules\Rendicion\Domain\Renglones\GrupoDeProveedor;

/**
 * F-52 (W-37): lo gastado en la obra por proveedor y por concepto, con los filtros Rubro (los 12 del resumen;
 * INSUMOS incluye EPP) y Período (por fecha efectiva, inclusive). El pie suma todo lo filtrado (RN-REN-36).
 */
final readonly class PorProveedorQuery
{
    public function __construct(
        private GastadoDeObras $gastado,
        private CatalogoDeRubros $catalogo,
    ) {}

    /**
     * @return array{grupos: list<GrupoDeProveedor>, total: numeric-string, comprobantes: int}
     */
    public function handle(ObraResumen $obra, ?string $rubro, ?string $desde, ?string $hasta): array
    {
        $renglones = $this->gastado->renglonesDeObra($obra->id, new FiltroRenglones(
            rubroClaves: $rubro === null ? null : $this->clavesDe($rubro),
            desde: $desde,
            hasta: $hasta,
        ));

        return [
            'grupos' => AgrupacionPorProveedor::agrupar($renglones),
            'total' => Decimal::suma(array_map(static fn (RenglonImputado $r): string => $r->total, $renglones)),
            'comprobantes' => AgrupacionPorProveedor::comprobantes($renglones),
        ];
    }

    /**
     * @return list<string> el rubro y los que suman en él ("insumos" → ["insumos", "epp"])
     */
    private function clavesDe(string $rubro): array
    {
        $claves = [$rubro];

        foreach ($this->catalogo->imputables() as $imputable) {
            if ($this->catalogo->resumeEn($imputable->clave) === $rubro) {
                $claves[] = $imputable->clave;
            }
        }

        return $claves;
    }
}
