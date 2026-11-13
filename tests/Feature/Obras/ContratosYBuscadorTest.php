<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Modules\Rendicion\Domain\Models\Rubro;
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
