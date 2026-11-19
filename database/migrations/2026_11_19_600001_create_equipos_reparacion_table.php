<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipos en reparación (pedidos.md §4.4, F-67, W-49): la solapa de la minuta como lista propia. El
 * inventario y el presupuesto del taller van en `adjuntos` (contextos `equipo_inventario` y
 * `equipo_presupuesto`, ADR-014). "Fecha" = cuándo se mandó al taller (resuelta DU-Q03). Sin importes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_reparacion', function (Blueprint $table): void {
            $table->id();
            $table->string('equipo', 150);
            $table->string('codigo', 40)->nullable();
            $table->text('trabajo');
            $table->string('taller', 150)->nullable();
            $table->string('estado', 12)->default('no_iniciada');
            $table->string('aprobacion', 12)->default('pendiente');
            $table->date('fecha')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_reparacion');
    }
};
