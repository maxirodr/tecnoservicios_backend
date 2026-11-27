<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DU-C03: la fila 13 de la tabla de permisos, "Pagos de facturas" (`pagos`, ver o editar, sin alcance), y su
 * configuración inicial (identidad.md §6.3). Idempotente: la función por `clave` y cada celda por (sector,
 * función) con `insertOrIgnore`, así no pisa lo que Gerencia ya cambió. Sectores que no existan, se saltean.
 */
return new class extends Migration
{
    private const CLAVE = 'pagos';

    private const NOMBRE = 'Pagos de facturas';

    /** Sector => nivel. El resto de los sectores, sin acceso. */
    private const CONFIGURACION = [
        'Gerencia de Administración' => 'editar',
        'Jefatura de Administración' => 'editar',
        'Dirección' => 'ver',
        'Compras' => 'ver',
    ];

    private const SIN_ACCESO = 'sin_acceso';

    public function up(): void
    {
        DB::table('funciones')->insertOrIgnore([
            'clave' => self::CLAVE,
            'nombre' => self::NOMBRE,
            'admite_editar' => true,
            'admite_alcance' => false,
            'orden' => ((int) DB::table('funciones')->max('orden')) + 1,
        ]);

        $funcionId = (int) DB::table('funciones')->where('clave', self::CLAVE)->value('id');
        $niveles = [];

        foreach (self::CONFIGURACION as $sector => $nivel) {
            $niveles[Str::of($sector)->ascii()->lower()->squish()->toString()] = $nivel;
        }

        foreach (DB::table('sectores')->get(['id', 'nombre_clave']) as $sector) {
            DB::table('permisos')->insertOrIgnore([
                'sector_id' => $sector->id,
                'funcion_id' => $funcionId,
                'nivel' => $niveles[$sector->nombre_clave] ?? self::SIN_ACCESO,
                'alcance' => 'todas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $funcionId = DB::table('funciones')->where('clave', self::CLAVE)->value('id');

        if ($funcionId !== null) {
            DB::table('permisos')->where('funcion_id', $funcionId)->delete();
            DB::table('funciones')->where('id', $funcionId)->delete();
        }
    }
};
