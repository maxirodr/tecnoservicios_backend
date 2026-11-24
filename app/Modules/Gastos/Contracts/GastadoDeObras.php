<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * Lo que Rendicion lee de Gastos (rendicion.md §12.4.1, resuelta DU-R08). Gastos lo implementa; hasta
 * entonces `GastadoDeObrasNulo`. Importes como string decimal con signo (ADR-008). Nunca trae anulados,
 * salvo `contarRenglonesPorMaterial`.
 */
interface GastadoDeObras
{
    /**
     * Gastado por obra y rubro, **sin** aplicar EPP → INSUMOS (lo hace Rendicion). Excluye anulados y
     * renglones sin obra; incluye negativos (devoluciones). Una sola consulta para todas las obras.
     * Una obra sin renglones no aparece.
     *
     * @param  list<int>  $obraIds
     * @return array<int, array<string, numeric-string>> obraId => [rubroClave => total]
     */
    public function totalesPorRubro(array $obraIds): array;

    /**
     * Renglones no anulados de una obra (W-11 últimos gastos, W-36, W-37, W-48), sin N+1.
     *
     * @return list<RenglonImputado>
     */
    public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array;

    /**
     * Cuántos renglones usan cada material, **anulados incluidos** (no se quita un material usado,
     * RN-REN-53). Un material sin renglones no aparece.
     *
     * @param  list<int>  $materialIds
     * @return array<int, int> materialId => cantidad de renglones
     */
    public function contarRenglonesPorMaterial(array $materialIds): array;
}
