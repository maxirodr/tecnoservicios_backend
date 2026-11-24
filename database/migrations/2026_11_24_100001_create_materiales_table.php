<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de materiales de cada obra para el control por cantidad (F-66, W-48; modelo-datos "materiales",
 * rendicion.md §4.2). Dueña Rendicion. Va antes que las tablas de Gastos porque sus renglones la
 * referencian (`material_id`). Lo retirado no se guarda acá: es la suma de los renglones (ADR-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('presupuesto_id')->nullable()->constrained('presupuestos')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('nombre_clave', 150);
            $table->string('unidad', 20);
            $table->decimal('cantidad_presupuestada', 14, 3)->nullable();
            $table->decimal('precio_presupuestado', 15, 2)->nullable();
            $table->boolean('fuera_de_presupuesto');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['obra_id', 'nombre_clave']);
        });

        // Completar la cantidad de uno fuera de presupuesto lo pasa a la lista (modelo-datos).
        DB::statement('ALTER TABLE materiales ADD CONSTRAINT materiales_fuera_sin_cantidad CHECK (fuera_de_presupuesto = (cantidad_presupuestada IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
