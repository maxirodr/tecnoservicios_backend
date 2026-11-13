<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use Illuminate\Support\Facades\DB;

/** F-60 · HU-010 · identidad.md §6.1 y §6.3 (RN-IDE-36): catálogo y configuración inicial por migración. */
final class ConfiguracionInicialTest extends IdentidadTestCase
{
    private const FUNCIONES = [
        'pedidos', 'equipos_reparacion', 'obras', 'estado_obra', 'presupuesto_rubro', 'monto_obra',
        'gastos', 'rendicion', 'control_cantidad', 'proveedores', 'usuarios_permisos', 'plazos_prioridad',
    ];

    /** §6.3, copiada de la especificación (E/V/- y P = solo sus obras). */
    private const ESPERADA = [
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

    private const NIVELES = ['E' => 'editar', 'V' => 'ver', '-' => 'sin_acceso'];

    public function test_migracion_carga_el_catalogo_de_doce_funciones(): void
    {
        $funciones = Funcion::query()->orderBy('orden')->get();

        $this->assertSame(self::FUNCIONES, $funciones->pluck('clave')->all());
        $this->assertTrue($funciones->every(fn (Funcion $funcion): bool => $funcion->admite_editar));
        $this->assertSame(
            ['pedidos', 'obras', 'presupuesto_rubro', 'monto_obra', 'gastos', 'rendicion', 'control_cantidad'],
            $funciones->where('admite_alcance', true)->pluck('clave')->values()->all(),
        );
    }

    public function test_migracion_carga_la_configuracion_inicial_del_cap_3(): void
    {
        $celdas = $this->celdas();

        foreach (self::ESPERADA as $sector => $fila) {
            foreach (self::FUNCIONES as $indice => $funcion) {
                $esperada = self::NIVELES[$fila[$indice][0]].'·'.(str_ends_with($fila[$indice], 'P') ? 'propias' : 'todas');
                $this->assertSame($esperada, $celdas["{$sector}|{$funcion}"] ?? null, "{$sector} × {$funcion}");
            }
        }

        $this->assertCount(11 * 12, $celdas);
    }

    public function test_migracion_de_permisos_es_idempotente(): void
    {
        DB::table('permisos')->where('nivel', 'ver')->limit(1)->update(['nivel' => 'editar']);
        $antes = $this->celdas();

        $migracion = require database_path('migrations/2026_11_13_100005_cargar_catalogo_y_configuracion_inicial_de_permisos.php');
        $migracion->up();

        $this->assertSame($antes, $this->celdas());
        $this->assertSame(12, Funcion::query()->count());
        $this->assertSame(11, DB::table('sectores')->count());
    }

    public function test_jefatura_de_administracion_no_edita_usuarios_y_la_gerencia_si(): void
    {
        $permisos = $this->app->make(Permisos::class);

        $this->assertTrue($permisos->puede($this->usuarioDe('Gerencia de Administración'), 'usuarios_permisos', Nivel::Editar));
        $this->assertFalse($permisos->puede($this->usuarioDe('Jefatura de Administración'), 'usuarios_permisos', Nivel::Ver));
    }

    public function test_operaciones_ve_gastos_y_no_los_edita(): void
    {
        $permisos = $this->app->make(Permisos::class);
        $operaciones = $this->usuarioDe('Gerencia de Operaciones');

        $this->assertTrue($permisos->puede($operaciones, 'gastos', Nivel::Ver));
        $this->assertFalse($permisos->puede($operaciones, 'gastos', Nivel::Editar));
    }

    public function test_ingenieria_ve_rendicion_solo_de_sus_obras_y_no_el_monto(): void
    {
        $permisos = $this->app->make(Permisos::class);
        $laura = $this->usuarioDe('Ingeniería de proyectos');

        $this->assertTrue($permisos->puede($laura, 'rendicion', Nivel::Ver));
        $this->assertSame(Alcance::Propias, $permisos->alcance($laura, 'rendicion'));
        $this->assertFalse($permisos->puede($laura, 'monto_obra', Nivel::Ver));
    }

    public function test_usuario_inactivo_no_puede_nada_aunque_su_sector_si(): void
    {
        $permisos = $this->app->make(Permisos::class);
        $inactivo = $this->usuarioDe('Gerencia de Administración', ['activo' => false]);

        $this->assertFalse($permisos->puede($inactivo, 'pedidos', Nivel::Ver));
    }

    /**
     * @return array<string, string> "Sector|funcion" => "nivel·alcance"
     */
    private function celdas(): array
    {
        return Permiso::query()->with(['funcion', 'sector'])->get()
            ->mapWithKeys(fn (Permiso $celda): array => [
                $celda->sector->nombre.'|'.$celda->funcion->clave => $celda->nivel->value.'·'.$celda->alcance->value,
            ])->all();
    }
}
