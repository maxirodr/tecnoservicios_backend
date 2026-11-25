<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CLAVE = 'pedidos';

    private const NOMBRE_ANTERIOR = 'Pedidos';

    private const NOMBRE = 'Tareas';

    /**
     * DU-C01: para el usuario, "Pedidos" pasa a "Tareas". Cambia solo el nombre visible de la función en la
     * tabla de permisos y en `/api/me`; la clave `pedidos`, las celdas y los permisos quedan como están.
     * Idempotente: solo toca la fila si todavía tiene el nombre anterior.
     */
    public function up(): void
    {
        DB::table('funciones')->where('clave', self::CLAVE)->where('nombre', self::NOMBRE_ANTERIOR)->update(['nombre' => self::NOMBRE]);
    }

    public function down(): void
    {
        DB::table('funciones')->where('clave', self::CLAVE)->where('nombre', self::NOMBRE)->update(['nombre' => self::NOMBRE_ANTERIOR]);
    }
};
