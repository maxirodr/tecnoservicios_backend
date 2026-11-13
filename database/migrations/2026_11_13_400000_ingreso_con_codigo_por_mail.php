<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-015: se entra con un código de 6 dígitos que llega por mail; no hay contraseñas.
 * Saca `users.password` y las tablas de links de contraseña (recuperación y bienvenida) y crea
 * `codigos_ingreso`: un código vigente por usuario, guardado hasheado (pedir otro reemplaza al anterior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_ingreso', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            // dateTime y no timestamp: en MariaDB 10.4 el primer TIMESTAMP NOT NULL se pisa solo con cada UPDATE.
            $table->dateTime('vence_at');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password');
        });

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('password_bienvenida_tokens');
    }

    public function down(): void
    {
        Schema::create('password_bienvenida_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('email');
        });

        Schema::dropIfExists('codigos_ingreso');
    }
};
