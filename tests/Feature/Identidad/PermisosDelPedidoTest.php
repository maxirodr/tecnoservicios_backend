<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use Illuminate\Support\Facades\DB;

/**
 * TL-05 (RN-IDE-14): la celda de la matriz se lee una vez por pedido y por sector y función; un cambio
 * vale desde el próximo pedido (o enseguida si se guarda la celda en el mismo pedido). Sin cache entre pedidos.
 */
final class PermisosDelPedidoTest extends IdentidadTestCase
{
    public function test_dentro_del_pedido_cada_celda_se_consulta_una_sola_vez(): void
    {
        $permisos = $this->app->make(Permisos::class);
        $laura = $this->usuarioDe('Ingeniería de proyectos');

        $consultas = $this->contarConsultas(function () use ($permisos, $laura): void {
            $permisos->puede($laura, 'obras', Nivel::Ver);
            $permisos->puede($laura, 'obras', Nivel::Editar);
            $permisos->alcance($laura, 'obras');
            $permisos->puede($laura, 'gastos', Nivel::Ver);
        });

        $this->assertSame(2, $consultas, 'Una por función (obras y gastos)');
        $this->assertSame($permisos, $this->app->make(Permisos::class), 'Misma instancia dentro del pedido');
    }

    public function test_guardar_una_celda_en_el_mismo_pedido_se_ve_enseguida(): void
    {
        $permisos = $this->app->make(Permisos::class);
        $laura = $this->usuarioDe('Ingeniería de proyectos');
        $this->assertFalse($permisos->puede($laura, 'proveedores', Nivel::Ver));

        $celda = Permiso::query()
            ->where('sector_id', $laura->sector_id)
            ->where('funcion_id', Funcion::query()->where('clave', 'proveedores')->valueOrFail('id'))
            ->firstOrFail();
        $celda->nivel = Nivel::Ver;
        $celda->save();

        $this->assertTrue($permisos->puede($laura, 'proveedores', Nivel::Ver));
    }

    public function test_al_terminar_el_pedido_se_olvida_lo_leido(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos');
        $this->actingAs($laura)->getJson('/api/me')->assertJsonPath('data.permisos.proveedores.nivel', 'sin_acceso');

        // Cambio directo en la base, sin eventos de modelo: solo lo ve un pedido nuevo.
        $this->celda('Ingeniería de proyectos', 'proveedores', 'ver');

        $this->actingAs($laura)->getJson('/api/me')->assertJsonPath('data.permisos.proveedores.nivel', 'ver');
    }

    private function contarConsultas(callable $accion): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $accion();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
