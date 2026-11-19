<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos (avisos.md §4, ADR-012): una fila por persona y evento, con la foto del momento en `datos` y un
 * `dedupe_key` único para que un reintento no duplique. `pedido_id` sin clave foránea: la tabla `pedidos`
 * la crea el módulo Pedidos y queda null-able para etapas siguientes. Los avisos no se borran (DU-P22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('datos');
            $table->string('dedupe_key', 120)->unique();
            $table->dateTime('leido_at')->nullable();
            $table->dateTime('created_at');

            // Contador de la campana y lista "la más nueva arriba" sobre el mismo índice (ADR-005).
            $table->index(['user_id', 'leido_at', 'created_at']);
        });

        Schema::create('preferencias_aviso', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->boolean('por_mail');
            $table->timestamps();

            $table->unique(['user_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferencias_aviso');
        Schema::dropIfExists('avisos');
    }
};
