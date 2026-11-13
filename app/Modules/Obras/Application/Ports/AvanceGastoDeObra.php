<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Ports;

/**
 * Puerto que implementa Rendicion (semana 3, obras.md §10.4): "% del presupuesto gastado" del
 * listado (RN-OBR-04). `null` = sin presupuesto cargado o sin rendición todavía ("—").
 */
interface AvanceGastoDeObra
{
    /**
     * @param  list<int>  $obraIds
     * @return array<int, numeric-string|null> obraId => porcentaje como string decimal ("45.20")
     */
    public function porcentajesGastados(array $obraIds): array;

    /**
     * @param  list<int>  $obraIds
     * @return array<int, bool> obraId => se pasó del 100 %
     */
    public function pasadas(array $obraIds): array;
}
