<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sectores del organigrama (W-40, identidad.md §4.2). El nombre es único entre los activos sin
     * distinguir mayúsculas, tildes ni espacios: `nombre_clave` es el nombre normalizado y la columna
     * generada `nombre_clave_activo` (null si está archivado) lleva el índice único.
     */
    public function up(): void
    {
        Schema::create('sectores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('nombre_clave', 80);
            $table->boolean('es_direccion')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('nombre_clave_activo', 80)->nullable()->storedAs('IF(activo, nombre_clave, NULL)');
            $table->timestamps();

            $table->unique('nombre_clave_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectores');
    }
};
