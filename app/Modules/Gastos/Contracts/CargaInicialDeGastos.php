<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

use App\Shared\CargaInicial\CargaRechazada;

/**
 * La carga inicial del gastado y del retirado de una obra (`importacion.md` §11.4, RN-IMP-23…29): Gastos la guarda
 * como un gasto `sin_documento` / `saldo_inicial` con el proveedor genérico, fechado en el corte y cargado por quien
 * aprobó; `GastoImputado` sale después del commit. Hay dos por obra: el **gastado** (renglones sin material, un
 * rubro cada uno) y el **retirado** (renglones con material, precio 0); se distinguen por eso.
 */
interface CargaInicialDeGastos
{
    /**
     * @param  'gastado'|'retirado'  $tipo
     * @param  list<RenglonInicial>  $renglones  al menos uno
     * @param  list<string>  $historial  líneas que se suman al historial del gasto (equivalencias, RN-IMP-17)
     *
     * @throws CargaRechazada
     */
    public function cargar(int $actorId, int $obraId, string $fechaCorte, string $tipo, array $renglones, array $historial = []): int;

    /**
     * La carga inicial vigente (no anulada) de la obra, de gastado o de retirado (RN-IMP-08, RN-IMP-27).
     *
     * @param  'gastado'|'retirado'  $tipo
     */
    public function vigente(int $obraId, string $tipo): ?CargaInicialVigente;

    /** Renglones no anulados de la obra de **otro** origen con fecha hasta `$fecha` inclusive (RN-IMP-25). */
    public function renglonesHastaFecha(int $obraId, string $fecha): int;
}
