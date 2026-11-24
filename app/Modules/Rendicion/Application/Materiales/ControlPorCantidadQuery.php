<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use App\Modules\Rendicion\Application\Rendicion\RendicionDeObraQuery;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Materiales\ColumnasDeFechas;
use App\Modules\Rendicion\Domain\Materiales\RetiradoDeMaterial;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\Renglones\OrdenDeRenglones;

/**
 * F-66 (W-48): la lista de materiales con lo retirado por fecha, resta llevar y su total, que cierra con lo
 * gastado en MATERIALES de la rendición (RN-REN-50, 51), y lo de "fuera de presupuesto" por renglón
 * (RN-REN-52). Lo retirado sale de los renglones de Gastos (ADR-007); W-48 no se cachea (RN-REN-60).
 */
final readonly class ControlPorCantidadQuery
{
    public const RUBRO_MATERIALES = 'materiales';

    public function __construct(
        private GastadoDeObras $gastado,
        private ObrasConsulta $obras,
        private RendicionDeObraQuery $rendicion,
        private CatalogoDeRubros $catalogo,
        private AccesoRendicion $acceso,
    ) {}

    public function handle(User $user, ObraResumen $obra): ControlPorCantidad
    {
        $materiales = Material::query()->where('obra_id', $obra->id)->orderBy('id')->get();
        $lista = $materiales->where('fuera_de_presupuesto', false)->values();
        $renglones = $materiales->isEmpty() ? [] : $this->gastado->renglonesDeObra($obra->id, new FiltroRenglones(soloConMaterial: true));
        $retirado = RetiradoDeMaterial::porMaterial($renglones);
        $idsDeLista = $lista->modelKeys();
        $columnas = ColumnasDeFechas::de(array_values(array_map(
            static fn (RenglonImputado $r): string => $r->fecha,
            array_filter($renglones, static fn (RenglonImputado $r): bool => in_array($r->materialId, $idsDeLista, true)),
        )));
        $numeros = $this->numerosDePresupuestos($obra->id);

        $filas = array_values($lista->map(fn (Material $m): FilaDeMaterial => self::fila($m, $retirado[$m->id] ?? RetiradoDeMaterial::nada(), $columnas, $numeros[$m->presupuesto_id ?? 0] ?? null))->all());
        $totalLista = Decimal::suma(array_map(static fn (FilaDeMaterial $f): string => $f->total, $filas));
        $gastadoEnMateriales = $this->rendicion->resumen($obra->id)->rubro(self::RUBRO_MATERIALES)->gastado ?? Decimal::suma([]);
        $diferencia = Decimal::resta($gastadoEnMateriales, $totalLista);

        return new ControlPorCantidad(
            $columnas,
            $filas,
            $totalLista,
            $gastadoEnMateriales,
            Decimal::esCero($diferencia) ? null : $diferencia,
            $this->fueraDePresupuesto($materiales->where('fuera_de_presupuesto', true)->keyBy('id')->all(), $renglones),
            [
                'editar' => $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Editar, $obra),
                'cargar_retiro' => $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Editar, $obra)
                    || $this->acceso->puede($user, ClaveFuncion::Rendicion, Nivel::Editar, $obra),
            ],
        );
    }

    private static function fila(Material $material, RetiradoDeMaterial $retirado, ColumnasDeFechas $columnas, ?string $presupuesto): FilaDeMaterial
    {
        $visibles = [];
        $agrupadas = [];

        foreach ($retirado->porFecha as $fecha => $cantidad) {
            $columnas->esAgrupada($fecha) ? $agrupadas[] = $cantidad : $visibles[$fecha] = $cantidad;
        }

        return new FilaDeMaterial(
            $material,
            $presupuesto,
            $visibles,
            $agrupadas === [] ? null : Decimal::suma($agrupadas, Decimal::CANTIDAD),
            $retirado->cantidad,
            Decimal::resta($material->cantidad_presupuestada ?? '0', $retirado->cantidad, Decimal::CANTIDAD),
            $retirado->total,
        );
    }

    /**
     * Una fila por renglón asociado a un material fuera de presupuesto, la fecha más nueva arriba.
     *
     * @param  array<int, Material>  $fuera
     * @param  list<RenglonImputado>  $renglones
     * @return list<array{material: Material, renglon: RenglonImputado, rubroNombre: string}>
     */
    private function fueraDePresupuesto(array $fuera, array $renglones): array
    {
        $nombres = [];
        foreach ($this->catalogo->imputables() as $rubro) {
            $nombres[$rubro->clave] = $rubro->nombre;
        }

        $delFuera = array_values(array_filter($renglones, static fn (RenglonImputado $r): bool => $r->materialId !== null && isset($fuera[$r->materialId])));

        return array_map(static fn (RenglonImputado $r): array => [
            'material' => $fuera[(int) $r->materialId],
            'renglon' => $r,
            'rubroNombre' => $nombres[$r->rubroClave] ?? $r->rubroClave,
        ], OrdenDeRenglones::ordenar($delFuera, FiltroRenglones::ORDEN_FECHA, 'desc'));
    }

    /**
     * @return array<int, string> presupuestoId => número
     */
    private function numerosDePresupuestos(int $obraId): array
    {
        $numeros = [];

        foreach ($this->obras->presupuestadoPorRubro($obraId)->presupuestos as $presupuesto) {
            $numeros[$presupuesto->id] = $presupuesto->numero;
        }

        return $numeros;
    }
}
