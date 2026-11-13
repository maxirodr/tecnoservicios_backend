<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links del mail de bienvenida (RN-IDE-09): mismo formato que password_reset_tokens, pero con su
     * propio vencimiento (7 días, broker `bienvenida` en config/auth.php).
     */
    public function up(): void
    {
        Schema::create('password_bienvenida_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_bienvenida_tokens');
    }
};
