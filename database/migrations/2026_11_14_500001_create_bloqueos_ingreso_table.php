<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEG-01 (enmienda 2026-11-13 de ADR-015): bloqueo progresivo del ingreso por código. Una fila por
 * usuario con los códigos quemados seguidos (invalidados por 5 fallos), los bloqueos ya cumplidos y
 * hasta cuándo está bloqueado. Un ingreso exitoso borra la fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos_ingreso', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('codigos_quemados')->default(0);
            $table->unsignedSmallInteger('bloqueos_cumplidos')->default(0);
            // dateTime y no timestamp: en MariaDB 10.4 el primer TIMESTAMP NOT NULL se pisa solo con cada UPDATE.
            $table->dateTime('bloqueado_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_ingreso');
    }
};
