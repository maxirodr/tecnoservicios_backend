<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabecera de un gasto o factura (F-39…F-47; modelo-datos "gastos", gastos.md §4.1). El total no se
 * guarda: es la suma de sus renglones (ADR-007). `numero_vigente` + único con el proveedor: una factura no
 * anulada no se carga dos veces (criterio de aceptación n.º 2, resuelta DU-G10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_documento', 15);
            $table->string('origen', 20);
            $table->string('numero', 40)->nullable();
            $table->string('numero_vigente', 40)->nullable()
                ->storedAs("IF(tipo_documento = 'factura' AND anulado_at IS NULL, numero, NULL)");
            $table->date('fecha');
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('rendido_por', 120)->nullable();
            $table->date('periodo_desde')->nullable();
            $table->date('periodo_hasta')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('cargado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_anulacion')->nullable();

            $table->unique(['proveedor_id', 'numero_vigente']);
            $table->index(['proveedor_id', 'fecha']);
            $table->index('numero');
            $table->index('fecha');
            $table->index('anulado_at');
            $table->index('origen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
