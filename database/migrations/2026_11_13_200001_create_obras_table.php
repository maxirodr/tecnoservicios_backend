<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obra / legajo (obras.md §4.1, modelo-datos.md). Nace `confirmada`; no se borra (RN-OBR-24).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obras', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_trabajo', 200);
            $table->string('cliente', 150);
            $table->string('planta', 150)->nullable();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->string('estado', 15)->default('confirmada');
            $table->date('fecha_inicio_prevista')->nullable();
            $table->date('fecha_fin_prevista')->nullable();
            $table->string('carpeta_drive_url', 500)->nullable();
            $table->timestamp('terminada_at')->nullable();
            $table->foreignId('terminada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('estado');
            $table->index('cliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};
