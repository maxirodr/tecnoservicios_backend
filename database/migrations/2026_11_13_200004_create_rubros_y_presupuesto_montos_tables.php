<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rubros (lista cerrada, cap. 9; el catálogo lo carga la migración siguiente) y lo presupuestado por
 * rubro de cada presupuesto (F-48). `rubro_id` null = el presupuesto se cargó "solo el total" (C16);
 * `rubro_clave` (generada) permite el único (presupuesto, rubro) aun con el null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubros', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre', 40)->unique();
            $table->unsignedSmallInteger('orden');
            $table->foreignId('resume_en_rubro_id')->nullable()->constrained('rubros')->restrictOnDelete();
            $table->boolean('se_presupuesta')->default(true);
            $table->boolean('carga_por_periodo')->default(false);
            $table->timestamps();
        });

        Schema::create('presupuesto_montos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->foreignId('rubro_id')->nullable()->constrained('rubros')->restrictOnDelete();
            $table->decimal('monto', 15, 2);
            $table->unsignedBigInteger('rubro_clave')->storedAs('COALESCE(rubro_id, 0)');
            $table->timestamps();

            $table->unique(['presupuesto_id', 'rubro_clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_montos');
        Schema::dropIfExists('rubros');
    }
};
