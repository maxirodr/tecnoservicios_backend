<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contactos (solicitante en planta y comprador, uno de cada tipo) y documentos de la obra
 * (certificados y otros; sus archivos o links van en `adjuntos`, contexto `documento_obra`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_contactos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('tipo', 12);
            $table->string('nombre', 120);
            $table->string('email', 190);
            $table->string('telefono', 50);
            $table->timestamps();

            $table->unique(['obra_id', 'tipo']);
        });

        Schema::create('obra_documentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('tipo', 12);
            $table->string('nombre', 120);
            $table->date('fecha')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['obra_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_documentos');
        Schema::dropIfExists('obra_contactos');
    }
};
