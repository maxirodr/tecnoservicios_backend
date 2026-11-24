<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Rendicion\Domain\Calculo\CalculoDeRendicion;
use App\Modules\Rendicion\Domain\Calculo\ResumenDeRendicion;

/**
 * El resumen de varias obras con **una consulta por agregado** (presupuestado y gastado agrupados por obra),
 * no una por obra (RN-REN-41, RN-REN-42). Lo usan W-12 y el "% gastado" de W-05 / W-07.
 */
final readonly class RendicionesDeVariasObras
{
    public function __construct(
        private ObrasConsulta $obras,
        private GastadoDeObras $gastado,
        private CalculoDeRendicion $calculo,
    ) {}

    /**
     * @param  list<int>  $obraIds
     * @return array<int, ResumenDeRendicion> obraId => resumen
     */
    public function de(array $obraIds): array
    {
        $ids = array_values(array_unique($obraIds));

        if ($ids === []) {
            return [];
        }

        $presupuestado = $this->obras->presupuestadoDeObras($ids);
        $gastado = $this->gastado->totalesPorRubro($ids);
        $resumenes = [];

        foreach ($ids as $id) {
            $resumenes[$id] = $this->calculo->calcular($presupuestado[$id] ?? new PresupuestadoDeObra([], '0.00', '0.00'), $gastado[$id] ?? []);
        }

        return $resumenes;
    }
}
