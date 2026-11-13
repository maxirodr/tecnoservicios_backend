<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presupuestos de la obra: principal + adicionales (obras.md §4.2, F-18). El N.º normalizado (con su
 * sufijo, sin la revisión) es único en todo el sistema (RN-OBR-12). `principal_de` (columna generada)
 * hace cumplir "exactamente un principal por obra" con un índice único.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->string('numero', 30)->unique();
            $table->string('revision', 10)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->unsignedBigInteger('principal_de')->nullable()->storedAs('IF(es_principal, obra_id, NULL)');
            $table->string('descripcion', 200)->nullable();
            $table->string('aprobacion_tipo', 20)->nullable();
            $table->string('aprobacion_numero', 60)->nullable();
            $table->string('modo_carga', 12)->default('sin_cargar');
            $table->decimal('monto_obra', 15, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique('principal_de');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
