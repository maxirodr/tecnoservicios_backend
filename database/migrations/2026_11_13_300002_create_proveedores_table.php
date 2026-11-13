<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base de proveedores (proveedores.md §4.1, F-55/F-56). Sin saldos, cuenta corriente ni pagos
     * (siguen en Tango, D-040).
     * - `nombre_clave` (normalización suave) es único: cada proveedor se carga una vez (RN-PRV-04).
     * - `nombre_normalizado` (fuerte) alimenta el aviso de parecidos (RN-PRV-08).
     * - `nombre_busqueda` (sin mayúsculas, tildes ni puntuación, con la forma societaria) es lo que
     *   recorre el buscador y el orden alfabético (RN-PRV-02/03).
     * - `cuit` guarda los 11 dígitos, único si está (RN-PRV-06/07).
     * - `generico_unico` (1 si es el genérico, si no null) asegura un solo genérico (RN-PRV-12).
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('nombre_clave', 150)->unique();
            $table->string('nombre_normalizado', 150)->index();
            $table->string('nombre_busqueda', 150)->index();
            $table->char('cuit', 11)->nullable()->unique();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 190)->nullable();
            $table->foreignId('rubro_habitual_id')->nullable()->constrained('rubros')->restrictOnDelete();
            $table->boolean('es_generico')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedTinyInteger('generico_unico')->nullable()->storedAs('IF(es_generico, 1, NULL)')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
