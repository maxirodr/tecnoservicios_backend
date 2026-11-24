<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Obras\Contracts\PresupuestoDeObra;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Domain\Importe;
use stdClass;

/**
 * Lo presupuestado de varias obras en **una** consulta (rendicion.md §12.4.2, RN-REN-41): cada presupuesto
 * con sus montos sumados por rubro; `rubro_id` null es lo cargado "solo total" (sin desglose, RN-OBR-37).
 */
final class PresupuestadoDeObrasQuery
{
    /** @var array<int, array<string, Importe>> */
    private array $porRubro = [];

    /** @var array<int, Importe> */
    private array $sinDesglose = [];

    /** @var array<int, array<int, stdClass>> */
    private array $presupuestos = [];

    /** @var array<int, Importe> */
    private array $totalDelPresupuesto = [];

    /**
     * @param  list<int>  $obraIds
     * @return array<int, PresupuestadoDeObra>
     */
    public function handle(array $obraIds): array
    {
        $this->porRubro = $this->sinDesglose = $this->presupuestos = $this->totalDelPresupuesto = [];

        foreach ($obraIds === [] ? [] : $this->filas($obraIds) as $fila) {
            $this->acumular($fila);
        }

        $resultado = [];
        foreach ($obraIds as $obraId) {
            $resultado[$obraId] = $this->armar($obraId);
        }

        return $resultado;
    }

    /**
     * @param  list<int>  $obraIds
     * @return iterable<stdClass>
     */
    private function filas(array $obraIds): iterable
    {
        return Presupuesto::query()
            ->leftJoin('presupuesto_montos', 'presupuesto_montos.presupuesto_id', '=', 'presupuestos.id')
            ->leftJoin('rubros', 'rubros.id', '=', 'presupuesto_montos.rubro_id')
            ->whereIn('presupuestos.obra_id', $obraIds)
            ->groupBy('presupuestos.id', 'rubros.clave')
            ->orderByDesc('presupuestos.es_principal')
            ->orderBy('presupuestos.id')
            ->selectRaw('presupuestos.id, presupuestos.obra_id, presupuestos.numero, presupuestos.revision, presupuestos.es_principal, presupuestos.modo_carga, COUNT(presupuesto_montos.id) as montos, rubros.clave as clave, SUM(presupuesto_montos.monto) as monto')
            ->toBase()
            ->get();
    }

    private function acumular(stdClass $fila): void
    {
        $obraId = (int) $fila->obra_id;
        $id = (int) $fila->id;
        $this->presupuestos[$obraId][$id] = $fila;
        $this->totalDelPresupuesto[$id] ??= Importe::cero();

        if ((int) $fila->montos === 0) {
            return;
        }

        $monto = Importe::desde((string) $fila->monto);
        $this->totalDelPresupuesto[$id] = $this->totalDelPresupuesto[$id]->sumar($monto);

        if ($fila->clave === null) {
            $this->sinDesglose[$obraId] = ($this->sinDesglose[$obraId] ?? Importe::cero())->sumar($monto);

            return;
        }

        $clave = (string) $fila->clave;
        $this->porRubro[$obraId][$clave] = ($this->porRubro[$obraId][$clave] ?? Importe::cero())->sumar($monto);
    }

    private function armar(int $obraId): PresupuestadoDeObra
    {
        $sinDesglose = $this->sinDesglose[$obraId] ?? Importe::cero();
        $total = $sinDesglose;
        $porRubro = [];

        foreach ($this->porRubro[$obraId] ?? [] as $clave => $monto) {
            $total = $total->sumar($monto);
            $porRubro[$clave] = $monto->valor;
        }

        $presupuestos = [];
        foreach ($this->presupuestos[$obraId] ?? [] as $id => $fila) {
            $presupuestos[] = new PresupuestoDeObra(
                $id,
                (string) $fila->numero,
                $fila->revision === null ? null : (string) $fila->revision,
                (bool) $fila->es_principal,
                ModoCarga::from((string) $fila->modo_carga)->value,
                $this->totalDelPresupuesto[$id]->valor,
            );
        }

        return new PresupuestadoDeObra($porRubro, $sinDesglose->valor, $total->valor, $presupuestos);
    }
}
