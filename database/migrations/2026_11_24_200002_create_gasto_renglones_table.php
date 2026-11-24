<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renglones de un gasto: **la única fuente del gastado** (ADR-007; modelo-datos "gasto_renglones",
 * gastos.md §4.2). `fecha` es la fecha efectiva con la que el renglón entra en la rendición (resuelta
 * DU-G01 y DU-R04). Sin unidad propia: la del material.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto_renglones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gasto_id')->constrained('gastos')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->date('fecha');
            $table->string('concepto', 250);
            $table->decimal('cantidad', 14, 3);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('total', 15, 2);
            $table->foreignId('obra_id')->nullable()->constrained('obras')->restrictOnDelete();
            $table->foreignId('rubro_id')->nullable()->constrained('rubros')->restrictOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materiales')->restrictOnDelete();
            $table->string('detalle', 250)->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'rubro_id']);
            $table->index(['obra_id', 'fecha']);
        });

        // Un renglón con obra tiene rubro (modelo-datos, rendicion.md §12.4.1 invariante 2).
        DB::statement('ALTER TABLE gasto_renglones ADD CONSTRAINT gasto_renglones_obra_con_rubro CHECK (obra_id IS NULL OR rubro_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('gasto_renglones');
    }
};
