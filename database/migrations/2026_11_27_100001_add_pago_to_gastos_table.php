<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturas pagas o impagas (DU-C03, gastos.md §5.14): fecha del pago y quién la marcó; vacío = impaga. No toca
 * la rendición. Índice para el filtro Pago de W-33.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('gastos', 'pagado_at')) {
            return;
        }

        Schema::table('gastos', function (Blueprint $table): void {
            $table->date('pagado_at')->nullable()->after('motivo_anulacion');
            $table->foreignId('pagado_por')->nullable()->after('pagado_at')->constrained('users')->restrictOnDelete();
            $table->index(['tipo_documento', 'pagado_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('gastos', 'pagado_at')) {
            return;
        }

        Schema::table('gastos', function (Blueprint $table): void {
            $table->dropIndex(['tipo_documento', 'pagado_at']);
            $table->dropConstrainedForeignId('pagado_por');
            $table->dropColumn('pagado_at');
        });
    }
};
