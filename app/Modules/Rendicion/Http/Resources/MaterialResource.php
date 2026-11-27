<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Resources;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Rendicion\Application\Materiales\ControlPorCantidad;
use App\Modules\Rendicion\Application\Materiales\FilaDeMaterial;
use App\Modules\Rendicion\Contracts\MaterialOpcion;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Concurrencia\VersionDeFila;

/** Formas JSON de W-48 (rendicion.md §12.2): `ControlPorCantidad`, `Material` y las opciones para Gastos. */
final class MaterialResource
{
    /**
     * @return array<string, mixed>
     */
    public static function material(Material $material, ?string $presupuestoNumero = null): array
    {
        return [
            'id' => $material->id,
            'nombre' => $material->nombre,
            'unidad' => $material->unidad,
            'presupuesto' => $material->presupuesto_id === null ? null : ['id' => $material->presupuesto_id, 'numero' => $presupuestoNumero],
            'cantidad_presupuestada' => $material->cantidad_presupuestada,
            'precio_presupuestado' => $material->precio_presupuestado,
            'fuera_de_presupuesto' => $material->fuera_de_presupuesto,
            'updated_at' => VersionDeFila::iso($material->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function control(ControlPorCantidad $control): array
    {
        return [
            'columnas' => [
                'fechas' => $control->columnas->fechas,
                // RN-IMP-30: "Hasta {fecha} · Carga inicial" o "· incluye carga inicial" si también agrupa otras fechas.
                'hasta' => $control->columnas->hasta === null ? null : [
                    'fecha' => $control->columnas->hasta,
                    'carga_inicial' => $control->columnas->cargaInicial,
                    'incluye_otras' => $control->columnas->agrupaOtras,
                ],
            ],
            'materiales' => array_map(static fn (FilaDeMaterial $f): array => [
                ...self::material($f->material, $f->presupuestoNumero),
                'retirado_por_fecha' => (object) $f->retiradoPorFecha,
                'retirado_hasta' => $f->retiradoHasta,
                'total_retirado' => $f->totalRetirado,
                'resta_llevar' => $f->restaLlevar,
                'total' => $f->total,
            ], $control->materiales),
            'total_lista' => $control->totalLista,
            'materiales_en_rendicion' => ['gastado' => $control->gastadoEnMateriales, 'sin_material' => $control->sinMaterial],
            'fuera_de_presupuesto' => array_map(static fn (array $fila): array => self::fuera($fila['material'], $fila['renglon'], $fila['rubroNombre']), $control->fueraDePresupuesto),
            'puede' => $control->puede,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function opcion(MaterialOpcion $opcion): array
    {
        return [
            'id' => $opcion->id,
            'nombre' => $opcion->nombre,
            'unidad' => $opcion->unidad,
            'fuera_de_presupuesto' => $opcion->fueraDePresupuesto,
            'resta_llevar' => $opcion->restaLlevar,
            'precio_presupuestado' => $opcion->precioPresupuestado,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fuera(Material $material, RenglonImputado $renglon, string $rubroNombre): array
    {
        return [
            'material_id' => $material->id,
            'nombre' => $material->nombre,
            'unidad' => $material->unidad,
            'fecha' => $renglon->fecha,
            'cantidad' => $renglon->cantidad,
            'total' => $renglon->total,
            'rubro' => ['clave' => $renglon->rubroClave, 'nombre' => $rubroNombre],
            'renglon_id' => $renglon->renglonId,
            'gasto_id' => $renglon->gastoId,
        ];
    }
}
