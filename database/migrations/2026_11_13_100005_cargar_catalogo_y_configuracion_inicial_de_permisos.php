<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Catálogo de 12 funciones (identidad.md §6.1): clave => [nombre, admite_editar, admite_alcance].
     * El orden del arreglo es el de la tabla W-15.
     */
    private const FUNCIONES = [
        'pedidos' => ['Pedidos', true, true],
        'equipos_reparacion' => ['Equipos en reparación', true, false],
        'obras' => ['Obras', true, true],
        'estado_obra' => ['Estado de obra', true, false],
        'presupuesto_rubro' => ['Presupuesto por rubro', true, true],
        'monto_obra' => ['Monto de obra y ganancia', true, true],
        'gastos' => ['Gastos y facturas', true, true],
        'rendicion' => ['Rendición', true, true],
        'control_cantidad' => ['Control por cantidad', true, true],
        'proveedores' => ['Proveedores', true, false],
        'usuarios_permisos' => ['Usuarios y permisos', true, false],
        'plazos_prioridad' => ['Plazos de la prioridad', true, false],
    ];

    /**
     * Configuración inicial (identidad.md §6.3, definitiva), en el orden de FUNCIONES.
     * E = editar · V = ver · - = sin acceso · sufijo P = solo sus obras (si no, todas).
     */
    private const CONFIGURACION = [
        'Dirección' => ['E', 'E', 'V', 'V', 'V', 'V', 'V', 'V', 'V', 'V', 'E', 'V'],
        'Calidad' => ['E', 'E', 'V', '-', '-', '-', '-', '-', '-', '-', '-', '-'],
        'Gerencia de Operaciones' => ['E', 'E', 'E', 'V', 'V', 'V', 'V', 'V', 'V', 'V', 'E', 'E'],
        'Jefatura de Proyectos' => ['E', 'E', 'E', 'V', 'V', '-', 'V', 'V', 'V', 'V', '-', '-'],
        'Ingeniería de proyectos' => ['E', 'E', 'E', 'V', 'VP', '-', 'VP', 'VP', 'VP', '-', '-', '-'],
        'Seguridad e Higiene' => ['E', 'E', 'V', '-', '-', '-', '-', '-', '-', '-', '-', '-'],
        'Gerencia de Administración' => ['E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E'],
        'Jefatura de Administración' => ['E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', '-', '-'],
        'Compras' => ['E', 'E', 'V', '-', 'E', '-', 'E', 'E', 'E', 'E', '-', '-'],
        'RRHH' => ['E', 'E', 'V', '-', '-', '-', '-', '-', '-', '-', '-', '-'],
        'Pañol y Logística' => ['E', 'E', 'V', '-', '-', '-', '-', '-', '-', '-', '-', '-'],
    ];

    private const SECTOR_DIRECCION = 'Dirección';

    private const NIVELES = ['E' => 'editar', 'V' => 'ver', '-' => 'sin_acceso'];

    /**
     * Idempotente por clave: funciones por `clave`, sectores por nombre normalizado y celdas por
     * (sector, función). Volver a correrla no duplica ni pisa lo que la empresa ya cambió.
     */
    public function up(): void
    {
        $funcionIds = $this->cargarFunciones();

        foreach (self::CONFIGURACION as $sector => $celdas) {
            $sectorId = $this->cargarSector($sector);

            foreach (array_keys(self::FUNCIONES) as $indice => $clave) {
                $this->cargarCelda($sectorId, $funcionIds[$clave], $celdas[$indice]);
            }
        }
    }

    public function down(): void
    {
        $sectorIds = DB::table('sectores')
            ->whereIn('nombre_clave', array_map($this->clave(...), array_keys(self::CONFIGURACION)))
            ->pluck('id');

        DB::table('permisos')->whereIn('sector_id', $sectorIds)->delete();
        DB::table('sectores')->whereIn('id', $sectorIds)->whereNotExists(
            fn ($query) => $query->select(DB::raw(1))->from('users')->whereColumn('users.sector_id', 'sectores.id'),
        )->delete();
        DB::table('funciones')->whereIn('clave', array_keys(self::FUNCIONES))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('permisos')->whereColumn('permisos.funcion_id', 'funciones.id'))
            ->delete();
    }

    /**
     * @return array<string, int> clave => id
     */
    private function cargarFunciones(): array
    {
        $orden = 0;

        foreach (self::FUNCIONES as $clave => [$nombre, $admiteEditar, $admiteAlcance]) {
            $orden++;
            DB::table('funciones')->insertOrIgnore([
                'clave' => $clave,
                'nombre' => $nombre,
                'admite_editar' => $admiteEditar,
                'admite_alcance' => $admiteAlcance,
                'orden' => $orden,
            ]);
        }

        /** @var array<string, int> $ids */
        $ids = DB::table('funciones')->pluck('id', 'clave')->map(fn (mixed $id): int => (int) $id)->all();

        return $ids;
    }

    private function cargarSector(string $nombre): int
    {
        $clave = $this->clave($nombre);
        $existente = DB::table('sectores')->where('nombre_clave', $clave)->value('id');

        if ($existente !== null) {
            return (int) $existente;
        }

        return (int) DB::table('sectores')->insertGetId([
            'nombre' => $nombre,
            'nombre_clave' => $clave,
            'es_direccion' => $nombre === self::SECTOR_DIRECCION,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function cargarCelda(int $sectorId, int $funcionId, string $celda): void
    {
        DB::table('permisos')->insertOrIgnore([
            'sector_id' => $sectorId,
            'funcion_id' => $funcionId,
            'nivel' => self::NIVELES[$celda[0]],
            'alcance' => str_ends_with($celda, 'P') ? 'propias' : 'todas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function clave(string $nombre): string
    {
        return Str::of($nombre)->ascii()->lower()->squish()->toString();
    }
};
