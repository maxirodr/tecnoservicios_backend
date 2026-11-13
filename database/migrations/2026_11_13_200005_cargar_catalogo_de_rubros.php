<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo cerrado de rubros (obras.md §4.6, cap. 9), escritos como en sus planillas.
 * Idempotente por `clave`: volver a correrla no duplica.
 */
return new class extends Migration
{
    /** clave => [nombre, se_presupuesta, carga_por_periodo]; el orden del arreglo es el de su hoja "General". */
    private const RUBROS = [
        'materiales' => ['MATERIALES', true, false],
        'insumos' => ['INSUMOS', true, false],
        'epp' => ['EPP', false, false],
        'subcontratos' => ['SUBCONTRATOS', true, false],
        'logistica' => ['LOGISTICA', true, false],
        'amortizacion' => ['AMORTIZACION', true, false],
        'mantenimiento' => ['MANTENIMIENTO', true, false],
        'obradores_banos' => ['OBRADORES/BAÑOS', true, false],
        'servicios_generales' => ['SERVICIOS GENERALES', true, false],
        'maquinas_herramientas' => ['MAQUINAS/HERRAMIENTAS', true, false],
        'viaticos' => ['VIATICOS', true, false],
        'fijos' => ['FIJOS', true, true],
        'horas_liquidadas' => ['HORAS LIQUIDADAS', true, true],
    ];

    /** EPP no se presupuesta: en el resumen suma en INSUMOS (C17). */
    private const RESUMEN = ['epp' => 'insumos'];

    public function up(): void
    {
        $orden = 0;

        foreach (self::RUBROS as $clave => [$nombre, $sePresupuesta, $cargaPorPeriodo]) {
            $orden++;
            DB::table('rubros')->updateOrInsert(['clave' => $clave], [
                'nombre' => $nombre,
                'orden' => $orden,
                'se_presupuesta' => $sePresupuesta,
                'carga_por_periodo' => $cargaPorPeriodo,
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        foreach (self::RESUMEN as $clave => $destino) {
            DB::table('rubros')->where('clave', $clave)->update([
                'resume_en_rubro_id' => DB::table('rubros')->where('clave', $destino)->value('id'),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('rubros')->whereIn('clave', array_keys(self::RESUMEN))->update(['resume_en_rubro_id' => null]);
        DB::table('rubros')->whereIn('clave', array_keys(self::RUBROS))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('presupuesto_montos')->whereColumn('presupuesto_montos.rubro_id', 'rubros.id'))
            ->delete();
    }
};
