<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedidos (la minuta), sus comentarios y seguidores (pedidos.md §4.1–§4.3). `solicitante_id` y
 * `fecha_pedido` nullable solo para la carga inicial (resuelta DU-P10); `fecha_entrega` también, por la
 * misma razón (DU-20: la completa el sector antes del arranque). Los links y archivos van en `adjuntos`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->restrictOnDelete();
            $table->text('tarea');
            $table->foreignId('sector_id')->constrained('sectores')->restrictOnDelete();
            $table->foreignId('propietario_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('con_quien', 150)->nullable();
            $table->date('fecha_pedido')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->text('aclaraciones')->nullable();
            $table->string('prioridad', 12);
            $table->boolean('prioridad_manual')->default(false);
            $table->string('estado', 12)->default('no_iniciada');
            $table->text('motivo_bloqueo')->nullable();
            $table->text('comentario_cierre')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->foreignId('completado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('carga_inicial')->default(false);
            $table->timestamps();

            $table->index(['propietario_id', 'estado', 'fecha_entrega']);
            $table->index(['solicitante_id', 'estado']);
            $table->index(['obra_id', 'estado']);
            $table->index(['sector_id', 'estado']);
            $table->index(['estado', 'fecha_entrega']);
        });

        Schema::create('pedido_comentarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('texto');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('pedido_seguidores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['pedido_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_seguidores');
        Schema::dropIfExists('pedido_comentarios');
        Schema::dropIfExists('pedidos');
    }
};
