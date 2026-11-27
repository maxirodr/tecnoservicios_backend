<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de corridas de la carga inicial (`importacion.md` §11.3). Solo conteos, claves y lo creado: **sin
 * valores de celdas** (RN-IMP-13). Lo importado vive en las tablas de cada módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacion_corridas', function (Blueprint $table): void {
            $table->id();
            $table->string('lote', 10);
            $table->string('modo', 12);
            $table->char('huella', 64);
            $table->foreignId('aprobo_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('estado', 15);
            $table->json('resumen')->nullable();
            $table->string('informe', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('terminada_at')->nullable();

            $table->index(['lote', 'huella', 'modo']);
        });

        Schema::create('importacion_filas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corrida_id')->constrained('importacion_corridas')->cascadeOnDelete();
            $table->string('hoja', 20);
            $table->string('clave', 150);
            $table->string('resultado', 15);
            $table->string('sujeto_type', 120)->nullable();
            $table->unsignedBigInteger('sujeto_id')->nullable();

            $table->index(['hoja', 'clave', 'resultado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_filas');
        Schema::dropIfExists('importacion_corridas');
    }
};
