<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency-Key (ADR-011): (user_id, clave) → respuesta guardada durante 24 h.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('clave');
            $table->string('ruta', 255);
            $table->char('payload_hash', 64);
            $table->string('status', 12);
            $table->json('respuesta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'clave']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
