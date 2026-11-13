<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién hizo qué (ADR-010, F-14). Solo inserción: no tiene updated_at.
 * `user_id` es null solo para acciones del sistema (comandos programados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table): void {
            $table->id();
            $table->string('sujeto_type', 120);
            $table->unsignedBigInteger('sujeto_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('accion', 40);
            $table->json('cambios')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sujeto_type', 'sujeto_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
