<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo presupuestado por rubro de cada presupuesto (F-48, obras.md §4.7). Corre después del catálogo de
 * rubros (`2026_11_13_300001`, dueño Rendicion). `rubro_id` null = el presupuesto se cargó "solo el
 * total" (C16); `rubro_clave` (generada) permite el único (presupuesto, rubro) aun con el null.
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }
};
