<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adjuntos (ADR-014): un archivo subido o un link, nunca los dos.
 * `sujeto_*` es null mientras el adjunto es temporal (se purga a las 24 h).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjuntos', function (Blueprint $table): void {
            $table->id();
            $table->string('sujeto_type', 120)->nullable();
            $table->unsignedBigInteger('sujeto_id')->nullable();
            $table->string('contexto', 30);
            $table->string('tipo', 8);
            $table->string('url', 1000)->nullable();
            $table->string('disco', 40)->nullable();
            $table->string('ruta', 255)->nullable();
            $table->string('nombre', 200);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('tamano_bytes')->nullable();
            $table->foreignId('subido_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sujeto_type', 'sujeto_id', 'contexto']);
        });

        DB::statement(
            "ALTER TABLE adjuntos ADD CONSTRAINT adjuntos_archivo_o_link CHECK (
                (tipo = 'link' AND url IS NOT NULL AND ruta IS NULL)
                OR (tipo = 'archivo' AND ruta IS NOT NULL AND url IS NULL)
            )"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};
