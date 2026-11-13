<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Todo usuario tiene sector; "depende de" (jefe_id) es opcional (RN-IDE-22). Los ciclos se
     * validan en el caso de uso.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sector_id')->after('telefono')->constrained('sectores')->restrictOnDelete();
            $table->foreignId('jefe_id')->nullable()->after('sector_id')->constrained('users')->restrictOnDelete();

            $table->index(['sector_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['jefe_id']);
            $table->dropForeign(['sector_id']);
            $table->dropIndex(['sector_id', 'activo']);
            $table->dropColumn(['jefe_id', 'sector_id']);
        });
    }
};
