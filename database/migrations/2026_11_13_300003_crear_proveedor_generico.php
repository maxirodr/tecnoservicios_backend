<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** RN-PRV-12 (resuelta DU-A18): el proveedor de los gastos personales de obra sin ticket (F-44). */
    private const NOMBRE = 'Gastos sin comprobante';

    /** Sus formas normalizadas (suave, fuerte y de búsqueda coinciden: no tiene forma societaria ni artículos). */
    private const NORMALIZADO = 'gastos sin comprobante';

    /** Idempotente: el genérico se identifica por `es_generico` (único por columna generada). */
    public function up(): void
    {
        if (DB::table('proveedores')->where('es_generico', true)->exists()) {
            return;
        }

        DB::table('proveedores')->insert([
            'nombre' => self::NOMBRE,
            'nombre_clave' => self::NORMALIZADO,
            'nombre_normalizado' => self::NORMALIZADO,
            'nombre_busqueda' => self::NORMALIZADO,
            'cuit' => null,
            'es_generico' => true,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('proveedores')->where('es_generico', true)->delete();
    }
};
