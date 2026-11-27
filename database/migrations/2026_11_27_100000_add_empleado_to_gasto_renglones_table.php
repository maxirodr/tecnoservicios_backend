<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parte de horas (DU-C02, gastos.md §5.13): la persona de cada renglón, como texto (no es un usuario).
 * Las horas van en `cantidad` y el valor hora en `precio_unitario`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('gasto_renglones', 'empleado')) {
            return;
        }

        Schema::table('gasto_renglones', function (Blueprint $table): void {
            $table->string('empleado', 120)->nullable()->after('detalle');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('gasto_renglones', 'empleado')) {
            Schema::table('gasto_renglones', function (Blueprint $table): void {
                $table->dropColumn('empleado');
            });
        }
    }
};
