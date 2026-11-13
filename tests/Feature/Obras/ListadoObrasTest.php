<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Illuminate\Support\Carbon;
use Tests\Feature\Obras\Support\ObrasTestCase;
use Tests\Feature\Obras\Support\PuertosFalsos;

/** HU-013 · F-15 · F-22 · F-03 (W-05): listado por estado, búsqueda y filtros. */
final class ListadoObrasTest extends ObrasTestCase
{
    public function test_listado_separa_en_ejecucion_confirmadas_y_terminadas_con_contadores(): void
    {
        $laura = $this->laura();
        $this->obraDe($laura, ObraEstado::EnEjecucion);
        $this->obraDe($laura, ObraEstado::EnEjecucion);
        $this->obraDe($laura);
        $terminada = $this->obraDe($laura, ObraEstado::Terminada);

        $this->actingAs($this->carla())->getJson('/api/obras')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.estado', 'en_ejecucion')
            ->assertJsonPath('meta.contadores', ['en_ejecucion' => 2, 'confirmada' => 1, 'terminada' => 1])
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($this->carla())->getJson('/api/obras?estado=terminada')
            ->assertJsonPath('data.0.id', $terminada->id);
    }

    public function test_fila_muestra_revision_y_cantidad_de_adicionales(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura, null, ['adicionales' => [['numero' => '2-131-2026 A1']]]);

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada')
            ->assertJsonPath('data.0.id', $obra->id)
            ->assertJsonPath('data.0.numero_presupuesto', '2-131-2026')
            ->assertJsonPath('data.0.revision', 'REV01')
            ->assertJsonPath('data.0.adicionales', 1)
            ->assertJsonPath('data.0.cliente', 'Planta Ejemplo SA')
            ->assertJsonPath('data.0.planta', 'Nave 3')
            ->assertJsonPath('data.0.responsable', ['id' => $laura->id, 'nombre' => 'Laura', 'activo' => true]);
    }

    public function test_pedidos_abiertos_y_porcentaje_gastado_son_nulos_en_semana_1(): void
    {
        $this->obraDe($this->laura(), ObraEstado::EnEjecucion);

        $this->actingAs($this->diego())->getJson('/api/obras')
            ->assertJsonPath('data.0.pedidos_abiertos', null)
            ->assertJsonPath('data.0.porcentaje_gastado', null)
            ->assertJsonPath('data.0.pasado_de_presupuesto', null)
            ->assertJsonPath('meta.pedidos_sin_obra_abiertos', null);
    }

    public function test_pedidos_abiertos_salen_del_puerto_en_una_consulta(): void
    {
        $puertos = PuertosFalsos::registrar($this->app);
        $obra = $this->obraDe($this->laura(), ObraEstado::EnEjecucion);
        $puertos->pedidosPorObra = [$obra->id => 3];
        $puertos->sinObra = 5;

        $this->actingAs($this->diego())->getJson('/api/obras')
            ->assertJsonPath('data.0.pedidos_abiertos', 3)
            ->assertJsonPath('meta.pedidos_sin_obra_abiertos', 5);
        $this->assertSame(1, $puertos->consultasDeConteo);

        $this->actingAs($this->diego())->getJson('/api/obras?buscar=ejemplo')->assertJsonPath('meta.pedidos_sin_obra_abiertos', null);
    }

    public function test_en_ejecucion_ordena_por_porcentaje_gastado_y_lo_oculta_sin_permiso_de_rendicion(): void
    {
        $puertos = PuertosFalsos::registrar($this->app);
        $laura = $this->laura();
        $baja = $this->obraDe($this->diego(), ObraEstado::EnEjecucion);
        $sinDato = $this->obraDe($this->diego(), ObraEstado::EnEjecucion);
        $alta = $this->obraDe($laura, ObraEstado::EnEjecucion);
        $puertos->porcentajes = [$baja->id => '45.20', $alta->id => '104.00'];
        $puertos->pasadas = [$alta->id => true];

        $this->actingAs($this->diego())->getJson('/api/obras')
            ->assertJsonPath('data.*.id', [$alta->id, $baja->id, $sinDato->id])
            ->assertJsonPath('data.0.porcentaje_gastado', '104.00')
            ->assertJsonPath('data.0.pasado_de_presupuesto', true);

        // Ingeniería: Rendición = Ver solo sus obras.
        $this->actingAs($laura)->getJson('/api/obras')
            ->assertJsonPath('data.0.porcentaje_gastado', '104.00')
            ->assertJsonPath('data.1.porcentaje_gastado', null);
        // Calidad: sin Rendición.
        $this->actingAs($this->usuarioDe('Calidad'))->getJson('/api/obras')->assertJsonPath('data.0.porcentaje_gastado', null);
    }

    public function test_confirmadas_por_fecha_de_alta_y_terminadas_por_fecha_de_terminada(): void
    {
        $laura = $this->laura();
        $vieja = $this->obraDe($laura);
        $vieja->forceFill(['created_at' => Carbon::now()->subDays(3)])->save();
        $nueva = $this->obraDe($laura);
        $primera = $this->obraDe($laura, ObraEstado::Terminada);
        $primera->forceFill(['terminada_at' => Carbon::now()->subDay()])->save();
        $ultima = $this->obraDe($laura, ObraEstado::Terminada);

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada')->assertJsonPath('data.*.id', [$nueva->id, $vieja->id]);
        $this->actingAs($laura)->getJson('/api/obras?estado=terminada')->assertJsonPath('data.*.id', [$ultima->id, $primera->id]);
    }

    public function test_filtro_por_responsable_aplica_a_las_tres_pestanas(): void
    {
        $laura = $this->laura();
        $diego = $this->diego();
        $suya = $this->obraDe($laura);
        $this->obraDe($diego);
        $this->obraDe($laura, ObraEstado::Terminada);

        $this->actingAs($diego)->getJson("/api/obras?estado=confirmada&responsable_id={$laura->id}")
            ->assertJsonPath('data.*.id', [$suya->id])
            ->assertJsonPath('meta.contadores', ['en_ejecucion' => 0, 'confirmada' => 1, 'terminada' => 1]);
    }

    public function test_busca_por_numero_de_cualquier_presupuesto_de_la_obra(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura, null, ['adicionales' => [['numero' => '2-140-2026']]]);
        $this->crearObra($laura, null, ['nombre_trabajo' => 'Tanque de agua', 'cliente' => 'Terminal Sur SA', 'planta' => null, 'presupuesto' => ['numero' => '1-087-2026']]);

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada&buscar=2-140')->assertJsonPath('data.*.id', [$obra->id]);
    }

    public function test_busca_numero_ignorando_separadores(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        foreach (['2131', '2-131', '2 131 2026', '2/131'] as $texto) {
            $this->actingAs($laura)->getJson('/api/obras?estado=confirmada&buscar='.urlencode($texto))
                ->assertJsonPath('data.*.id', [$obra->id]);
        }
    }

    public function test_busca_por_cliente_planta_o_trabajo_sin_tildes_en_las_tres_pestanas(): void
    {
        $laura = $this->laura();
        $piso = $this->crearObra($laura);
        $otra = $this->crearObra($laura, null, ['nombre_trabajo' => 'Galpón de depósito', 'cliente' => 'Terminal Sur SA', 'planta' => 'Nave 3', 'presupuesto' => ['numero' => '3-200-2026']]);
        $otra->forceFill(['estado' => ObraEstado::Terminada])->save();

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada&buscar=PLANTA ejemplo')
            ->assertJsonPath('data.*.id', [$piso->id])
            ->assertJsonPath('meta.contadores', ['en_ejecucion' => 0, 'confirmada' => 1, 'terminada' => 0]);
        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada&buscar=nave')
            ->assertJsonPath('meta.contadores', ['en_ejecucion' => 0, 'confirmada' => 1, 'terminada' => 1]);
        $this->actingAs($laura)->getJson('/api/obras?estado=terminada&buscar=galpon')->assertJsonPath('data.*.id', [$otra->id]);
    }

    public function test_mas_de_25_obras_se_paginan_sin_perder_la_pestana_ni_el_filtro(): void
    {
        $laura = $this->laura();
        Obra::factory()->count(26)->deResponsable($laura)->create();

        $this->actingAs($laura)->getJson("/api/obras?estado=confirmada&responsable_id={$laura->id}&page=2")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_con_alcance_propias_solo_ve_sus_obras(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'ver', 'propias');
        $laura = $this->laura();
        $suya = $this->obraDe($laura);
        $this->obraDe($this->diego());

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada')
            ->assertJsonPath('data.*.id', [$suya->id])
            ->assertJsonPath('meta.contadores.confirmada', 1);
    }

    public function test_sin_obras_ver_no_lista(): void
    {
        $this->actingAs($this->sinAcceso())->getJson('/api/obras')->assertForbidden()->assertJsonPath('data', null);
    }

    public function test_sin_sesion_da_401(): void
    {
        $this->getJson('/api/obras')->assertUnauthorized();
    }

    public function test_listado_no_hace_una_consulta_por_obra(): void
    {
        $laura = $this->laura();
        Obra::factory()->count(5)->deResponsable($laura)->create();
        $this->assertSame(5, Presupuesto::query()->count());

        $this->actingAs($laura)->getJson('/api/obras?estado=confirmada')->assertOk()->assertJsonCount(5, 'data');
    }
}
