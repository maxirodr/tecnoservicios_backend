<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Montos;

use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Modules\Rendicion\Domain\Models\Rubro;
use App\Shared\Domain\Importe;

/**
 * W-13 (`GET /api/obras/{id}/presupuesto-por-rubro`, RN-OBR-32/37): una columna por presupuesto y
 * "Total de la obra" = suma por rubro de todos. Un presupuesto "solo total" suma en el Total y
 * muestra "—" (null) en los rubros. `monto_obra` solo viaja si `$conMontoObra` (RN-OBR-36).
 */
final readonly class PresupuestoPorRubroQuery
{
    public function __construct(private CatalogoRubros $rubros) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Obra $obra, bool $conMontoObra): array
    {
        $rubros = $this->rubros->presupuestables();
        $claves = $this->rubros->todos()->pluck('clave', 'id');
        $presupuestos = $obra->presupuestos()->with('montos')->get();

        $porRubro = array_fill_keys($rubros->pluck('clave')->all(), Importe::cero());
        $totalObra = Importe::cero();
        $montoObra = Importe::cero();
        $columnas = [];

        foreach ($presupuestos as $presupuesto) {
            [$montos, $total] = self::columna($presupuesto, $claves->all(), array_keys($porRubro));

            foreach ($montos as $clave => $monto) {
                $porRubro[$clave] = $porRubro[$clave]->sumar(Importe::desde($monto ?? '0'));
            }

            $totalObra = $totalObra->sumar(Importe::desde($total ?? '0'));
            $montoObra = $montoObra->sumar(Importe::desde($presupuesto->monto_obra ?? '0'));
            $columnas[] = [
                'id' => $presupuesto->id,
                'numero' => $presupuesto->numero,
                'revision' => $presupuesto->revision,
                'es_principal' => $presupuesto->es_principal,
                'modo_carga' => $presupuesto->modo_carga->value,
                'montos' => $montos,
                'total' => $total,
                ...($conMontoObra ? ['monto_obra' => $presupuesto->monto_obra] : []),
            ];
        }

        return [
            'rubros' => $rubros->map(static fn (Rubro $r): array => ['clave' => $r->clave, 'nombre' => $r->nombre])->all(),
            'presupuestos' => $columnas,
            'total_obra' => [
                'por_rubro' => array_map(static fn (Importe $i): string => $i->valor, $porRubro),
                'total' => $totalObra->valor,
                ...($conMontoObra ? ['monto_obra' => $montoObra->valor] : []),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $claves  rubro_id => clave
     * @param  list<string>  $presupuestables
     * @return array{0: array<string, string|null>, 1: string|null}
     */
    private static function columna(Presupuesto $presupuesto, array $claves, array $presupuestables): array
    {
        $montos = array_fill_keys($presupuestables, null);

        if ($presupuesto->modo_carga === ModoCarga::SinCargar) {
            return [$montos, null];
        }

        $total = Importe::cero();

        foreach ($presupuesto->montos as $fila) {
            /** @var PresupuestoMonto $fila */
            if ($fila->rubro_id !== null) {
                $montos[$claves[$fila->rubro_id]] = $fila->monto;
            }

            $total = $total->sumar(Importe::desde($fila->monto));
        }

        return [$montos, $total->valor];
    }
}
