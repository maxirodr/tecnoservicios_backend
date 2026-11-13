<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lista cerrada de rubros de su rendición (obras.md §4.6, modelo-datos "Rendición"), dueña Rendicion.
     * clave => [nombre, se_presupuesta, carga_por_periodo]; el orden del arreglo es el de su hoja "General".
     * EPP no se presupuesta y en el resumen suma en INSUMOS.
     */
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

    private const RESUMEN = ['epp' => 'insumos'];

    /**
     * Idempotente: si otra rama ya creó la tabla (Obras usa el mismo catálogo) no la recrea, y las
     * filas se cargan por `clave` sin duplicar ni pisar.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rubros')) {
            Schema::create('rubros', function (Blueprint $table) {
                $table->id();
                $table->string('clave', 40)->unique();
                $table->string('nombre', 40)->unique();
                $table->unsignedSmallInteger('orden');
                $table->foreignId('resume_en_rubro_id')->nullable()->constrained('rubros')->restrictOnDelete();
                $table->boolean('se_presupuesta');
                $table->boolean('carga_por_periodo');
            });
        }

        $orden = 0;
        foreach (self::RUBROS as $clave => [$nombre, $sePresupuesta, $cargaPorPeriodo]) {
            $orden++;
            DB::table('rubros')->insertOrIgnore([
                'clave' => $clave,
                'nombre' => $nombre,
                'orden' => $orden,
                'se_presupuesta' => $sePresupuesta,
                'carga_por_periodo' => $cargaPorPeriodo,
            ]);
        }

        foreach (self::RESUMEN as $clave => $resumeEn) {
            DB::table('rubros')->where('clave', $clave)->update([
                'resume_en_rubro_id' => DB::table('rubros')->where('clave', $resumeEn)->value('id'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};
