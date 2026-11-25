<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de la carga inicial en el registro de actividad (`importacion.md` RN-IMP-39): el historial de la obra,
 * el recorrido de la tarea y el del gasto dicen "(carga inicial)".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->boolean('carga_inicial')->default(false)->after('cambios');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table): void {
            $table->dropColumn('carga_inicial');
        });
    }
};
