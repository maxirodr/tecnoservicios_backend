<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Interfaz pública de Obras para los demás módulos (obras.md §10.4). `esResponsable` y
 * `obrasDondeEsResponsable` resuelven el alcance "solo sus obras" de todas las funciones de obra.
 * Una obra sin presupuesto por rubro igual existe y es elegible (HU-019 criterio 2).
 */
interface ObrasConsulta
{
    public function existe(int $obraId): bool;

    public function resumen(int $obraId): ?ObraResumen;

    public function esResponsable(int $userId, int $obraId): bool;

    /**
     * @return list<int>
     */
    public function obrasDondeEsResponsable(int $userId): array;

    public function presupuestadoPorRubro(int $obraId): PresupuestadoDeObra;
}
