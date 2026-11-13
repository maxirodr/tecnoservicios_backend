<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matriz sector × función (ADR-006, identidad.md §4.3 y §6.1). Una celda por (sector, función).
     */
    public function up(): void
    {
        Schema::create('funciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre', 80);
            $table->boolean('admite_editar');
            $table->boolean('admite_alcance');
            $table->unsignedSmallInteger('orden');
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->cascadeOnDelete();
            $table->foreignId('funcion_id')->constrained('funciones')->restrictOnDelete();
            $table->string('nivel', 12);
            $table->string('alcance', 10);
            $table->timestamps();

            $table->unique(['sector_id', 'funcion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('funciones');
    }
};
