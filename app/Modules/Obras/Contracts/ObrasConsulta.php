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

    /**
     * Lo presupuestado de varias obras en una sola consulta (W-12, `AvanceGastoDeObra`). Trae todas las
     * pedidas, también las que no tienen presupuestos cargados (en cero).
     *
     * @param  list<int>  $obraIds
     * @return array<int, PresupuestadoDeObra> obraId => presupuestado
     */
    public function presupuestadoDeObras(array $obraIds): array;

    /** Monto de la obra (RN-REN-23, 24); null si ningún presupuesto tiene monto cargado. */
    public function montoDeObra(int $obraId): ?MontoDeObra;

    /**
     * Las obras En ejecución (W-12), por id; con `$soloEstas`, solo las de esa lista (alcance "solo sus obras").
     *
     * @param  list<int>|null  $soloEstas
     * @return list<ObraResumen>
     */
    public function obrasEnEjecucion(?array $soloEstas): array;

    /**
     * Selector de obras al cargar un pedido (resuelta DU-P20): **todas** las obras, en cualquier estado
     * (las terminadas también, marcadas por su estado: DU-A15), sin mirar el presupuesto por rubro
     * (HU-019 c2). Busca en el trabajo, el cliente y el N.º; primero En ejecución, después Confirmadas y
     * al final Terminadas. Con `$soloEstas`, solo las de esa lista, antes de cortar en `$limite` (alcance
     * "solo sus obras", gastos.md DU-G12).
     *
     * @param  list<int>|null  $soloEstas
     * @return list<ObraResumen>
     */
    public function opciones(?string $texto, int $limite, ?array $soloEstas = null): array;
}
