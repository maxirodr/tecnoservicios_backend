<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** Contratos públicos de Obras (obras.md §10.4), puerto de Identidad (RN-IDE-24) y buscador general (F-03). */
final class ContratosYBuscadorTest extends ObrasTestCase
{
    public function test_obras_del_responsable_cuenta_las_activas_para_identidad(): void
    {
        $hernan = $this->hernan();
        $this->obraDe($hernan);
        $this->obraDe($hernan, ObraEstado::EnEjecucion);
        $this->obraDe($hernan, ObraEstado::Terminada);

        $this->assertSame(2, $this->app->make(ObrasDelResponsable::class)->cantidad($hernan));
        $this->actingAs($this->usuarioDe('Gerencia de Administración'))->getJson("/api/usuarios/{$hernan->id}")
            ->assertOk()
            ->assertJsonPath('data.obras_como_responsable', 2);
    }

    public function test_obras_consulta_resume_y_resuelve_el_alcance_propias(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $consulta = $this->app->make(ObrasConsulta::class);

        $this->assertTrue($consulta->existe($obra->id));
        $this->assertFalse($consulta->existe(999999));
        $this->assertTrue($consulta->esResponsable($laura->id, $obra->id));
        $this->assertFalse($consulta->esResponsable($this->diego()->id, $obra->id));
        $this->assertSame([$obra->id], $consulta->obrasDondeEsResponsable($laura->id));
        $resumen = $consulta->resumen($obra->id);
        $this->assertNotNull($resumen);
        $this->assertSame('confirmada', $resumen->estado);
        $this->assertSame($laura->id, $resumen->responsableId);
        $this->assertNull($consulta->resumen(999999));
    }

    public function test_presupuestado_por_rubro_suma_todos_los_presupuestos_y_separa_el_solo_total(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $principal = Presupuesto::query()->where('obra_id', $obra->id)->sole();
        $adicional = Presupuesto::query()->create(['obra_id' => $obra->id, 'numero' => '2-900-2026', 'created_by' => $diego->id]);
        $materiales = Rubro::query()->where('clave', 'materiales')->valueOrFail('id');
        PresupuestoMonto::query()->create(['presupuesto_id' => $principal->id, 'rubro_id' => $materiales, 'monto' => '1000.50']);
        PresupuestoMonto::query()->create(['presupuesto_id' => $adicional->id, 'rubro_id' => null, 'monto' => '200.00']);

        $presupuestado = $this->app->make(ObrasConsulta::class)->presupuestadoPorRubro($obra->id);

        $this->assertSame(['materiales' => '1000.50'], $presupuestado->porRubro);
        $this->assertSame('200.00', $presupuestado->sinDesglose);
        $this->assertSame('1200.50', $presupuestado->total);
    }

    public function test_presupuestado_de_varias_obras_trae_cada_presupuesto_con_su_modo_en_una_consulta(): void
    {
        $diego = $this->diego();
        $mixta = $this->obraDe($diego);
        $vacia = $this->obraDe($diego);
        $principal = Presupuesto::query()->where('obra_id', $mixta->id)->sole();
        $principal->update(['modo_carga' => 'por_rubro']);
        $adicional = Presupuesto::query()->create(['obra_id' => $mixta->id, 'numero' => '2-900-2026 A1', 'modo_carga' => 'solo_total', 'created_by' => $diego->id]);
        $sinCargar = Presupuesto::query()->create(['obra_id' => $mixta->id, 'numero' => '2-900-2026 A2', 'created_by' => $diego->id]);
        foreach (['materiales' => '1000.50', 'insumos' => '99.50'] as $clave => $monto) {
            PresupuestoMonto::query()->create(['presupuesto_id' => $principal->id, 'rubro_id' => Rubro::query()->where('clave', $clave)->valueOrFail('id'), 'monto' => $monto]);
        }
        PresupuestoMonto::query()->create(['presupuesto_id' => $adicional->id, 'rubro_id' => null, 'monto' => '200.00']);

        DB::enableQueryLog();
        $todas = $this->app->make(ObrasConsulta::class)->presupuestadoDeObras([$mixta->id, $vacia->id]);
        $this->assertCount(1, DB::getQueryLog());

        // Por clave: el orden de los rubros dentro de `porRubro` no es parte del contrato (el GROUP BY no lo fija).
        $porRubro = $todas[$mixta->id]->porRubro;
        ksort($porRubro);
        $this->assertSame(['insumos' => '99.50', 'materiales' => '1000.50'], $porRubro);
        $this->assertSame(['200.00', '1300.00'], [$todas[$mixta->id]->sinDesglose, $todas[$mixta->id]->total]);
        $this->assertSame(
            [[$principal->id, 'por_rubro', '1100.00', true], [$adicional->id, 'solo_total', '200.00', false], [$sinCargar->id, 'sin_cargar', '0.00', false]],
            array_map(static fn ($p): array => [$p->id, $p->modoCarga, $p->total, $p->esPrincipal], $todas[$mixta->id]->presupuestos),
        );
        $this->assertSame([[], '0.00', '0.00'], [$todas[$vacia->id]->porRubro, $todas[$vacia->id]->sinDesglose, $todas[$vacia->id]->total]);
        $this->assertSame('sin_cargar', $todas[$vacia->id]->presupuestos[0]->modoCarga);
    }

    public function test_monto_de_obra_suma_los_cargados_y_es_null_sin_ninguno(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $consulta = $this->app->make(ObrasConsulta::class);
        $sinMonto = $consulta->montoDeObra($obra->id);
        $this->assertNull($sinMonto);

        $principal = Presupuesto::query()->where('obra_id', $obra->id)->sole();
        $principal->update(['monto_obra' => '15000000.00']);
        Presupuesto::query()->create(['obra_id' => $obra->id, 'numero' => '2-900-2026 A1', 'created_by' => $diego->id]);

        $monto = $consulta->montoDeObra($obra->id);
        $this->assertNotNull($monto);
        $this->assertSame('15000000.00', $monto->total);
        $this->assertSame([[$principal->numero, '15000000.00'], ['2-900-2026 A1', null]], array_map(static fn ($p): array => [$p->numero, $p->monto], $monto->porPresupuesto));
    }

    public function test_obras_en_ejecucion_trae_solo_las_en_ejecucion_y_respeta_la_lista(): void
    {
        $hernan = $this->hernan();
        $enEjecucion = $this->obraDe($hernan, ObraEstado::EnEjecucion);
        $otra = $this->obraDe($this->laura(), ObraEstado::EnEjecucion);
        $this->obraDe($hernan);
        $this->obraDe($hernan, ObraEstado::Terminada);
        $consulta = $this->app->make(ObrasConsulta::class);

        $this->assertSame([$enEjecucion->id, $otra->id], array_map(static fn ($o): int => $o->id, $consulta->obrasEnEjecucion(null)));
        $this->assertSame([$enEjecucion->id], array_map(static fn ($o): int => $o->id, $consulta->obrasEnEjecucion([$enEjecucion->id])));
        $this->assertSame([], $consulta->obrasEnEjecucion([]));
    }

    public function test_buscador_general_encuentra_obras_por_numero_parcial_en_cualquier_estado(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura, null, ['nombre_trabajo' => 'Reparación de losa tanque B', 'cliente' => 'Terminal Sur SA', 'planta' => null, 'presupuesto' => ['numero' => '1-087-2026']]);
        Presupuesto::query()->firstOrFail()->obra()->firstOrFail()->forceFill(['estado' => ObraEstado::Terminada])->save();

        $this->actingAs($laura)->getJson('/api/buscar?q=087')
            ->assertOk()
            ->assertJsonPath('data.grupos.0.tipo', 'obras')
            ->assertJsonPath('data.grupos.0.cantidad', 1)
            ->assertJsonPath('data.grupos.0.resultados.0.numero', '1-087-2026')
            ->assertJsonPath('data.grupos.0.resultados.0.estado', 'terminada');
    }

    public function test_buscar_sin_tildes_encuentra_cliente_con_tildes(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura, null, ['cliente' => 'Cerámica Ñandú SA', 'planta' => null]);

        $this->actingAs($laura)->getJson('/api/buscar?q=ceramica nandu')
            ->assertJsonPath('data.grupos.0.cantidad', 1)
            ->assertJsonPath('data.grupos.0.resultados.0.cliente', 'Cerámica Ñandú SA');
    }

    public function test_buscador_respeta_el_alcance_solo_sus_obras_y_sin_acceso_no_muestra_el_grupo(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'ver', 'propias');
        $laura = $this->laura();
        $this->obraDe($this->diego());
        $suya = $this->obraDe($laura);

        $this->actingAs($laura)->getJson('/api/buscar?q=ejemplo')
            ->assertJsonPath('data.grupos.0.cantidad', 1)
            ->assertJsonPath('data.grupos.0.resultados.0.id', $suya->id);

        $grupos = $this->actingAs($this->sinAcceso())->getJson('/api/buscar?q=ejemplo')->assertOk()->json('data.grupos');
        $this->assertNotContains('obras', array_column((array) $grupos, 'tipo'));
    }
}
