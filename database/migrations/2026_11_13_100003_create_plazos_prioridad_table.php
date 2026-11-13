<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Valores de arranque (cap. 4.2: "urgente hasta 1 día, prioritario hasta 7"). */
    private const URGENTE_INICIAL = 1;

    private const PRIORITARIO_INICIAL = 7;

    /**
     * Plazos de la prioridad (F-68, W-50, identidad.md §4.4): una sola fila. "Normal" se deriva.
     */
    public function up(): void
    {
        Schema::create('plazos_prioridad', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('urgente_hasta_dias');
            $table->unsignedSmallInteger('prioritario_hasta_dias');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (! DB::table('plazos_prioridad')->exists()) {
            DB::table('plazos_prioridad')->insert([
                'urgente_hasta_dias' => self::URGENTE_INICIAL,
                'prioritario_hasta_dias' => self::PRIORITARIO_INICIAL,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plazos_prioridad');
    }
};
