<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-059 c4/c5 · F-61 · RN-PED-33 · RN-PED-52 · DU-P17 · DU-P20: alcance, puerto `PedidosDeObra` y contratos. */
final class AlcanceYContratosTest extends PedidosTestCase
{
    public function test_alcance_todas_ve_todos_los_pedidos(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->sofia())->getJson("/api/pedidos/{$pedido->id}")->assertOk();
    }

    public function test_pedido_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'pedidos', 'editar', 'propias');
        $laura = $this->laura();
        $carla = $this->carla();
        $ajeno = $this->crear($this->sofia(), $carla);
        $suyo = $this->crear($laura, $carla);

        $this->actingAs($laura)->getJson("/api/pedidos/{$ajeno->id}")->assertNotFound();
        $this->actingAs($laura)->getJson("/api/pedidos/{$suyo->id}")->assertOk();
    }

    public function test_alcance_propias_ve_lo_que_pidio_tiene_sigue_y_de_su_gente(): void
    {
        $this->celda('Jefatura de Administración', 'pedidos', 'editar', 'propias');
        $diego = $this->diego();
        $carla = $this->persona('Carla', 'Compras', ['jefe_id' => $diego->id]);
        $sofia = $this->sofia();
        $laura = $this->laura();
        $deSuGente = $this->crear($laura, $carla);
        $loSigue = $this->crear($laura, $sofia, null, ['seguidores' => [$diego->id]]);
        $loTiene = $this->crear($laura, $diego);
        $ajeno = $this->crear($laura, $sofia);

        foreach ([$deSuGente, $loSigue, $loTiene] as $pedido) {
            $this->actingAs($diego)->getJson("/api/pedidos/{$pedido->id}")->assertOk();
        }

        $this->actingAs($diego)->getJson("/api/pedidos/{$ajeno->id}")->assertNotFound();
    }

    public function test_alcance_propias_ve_los_sin_propietario_de_su_sector(): void
    {
        $this->celda('Compras', 'pedidos', 'editar', 'propias');
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->actingAs($pablo)->getJson("/api/pedidos/{$pedido->id}")->assertOk()->assertJsonPath('data.puede.tomar', true);
    }

    public function test_pedidos_abiertos_por_obra_en_una_consulta(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $otra = $this->obra(nombre: 'Piso industrial nave 3');
        $vacia = $this->obra(nombre: 'Impermeabilización cubierta');
        $this->crear($laura, $carla, $obra);
        $this->crear($laura, $carla, $obra);
        $completado = $this->crear($laura, $carla, $otra);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->actingAs($laura);

        DB::enableQueryLog();
        $conteos = $this->app->make(PedidosDeObra::class)->contarAbiertosPorObra([$obra->id, $otra->id, $vacia->id]);

        $this->assertSame([$obra->id => 2, $otra->id => 0, $vacia->id => 0], $conteos);
        $this->assertCount(1, array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains((string) $q['query'], 'from `pedidos`')));
    }

    public function test_cuenta_abiertos_sin_obra(): void
    {
        $laura = $this->laura();
        $this->crear($laura, $this->carla());
        $this->crear($laura, $this->carla(), $this->obra());

        $this->actingAs($laura)->getJson('/api/obras')->assertJsonPath('meta.pedidos_sin_obra_abiertos', 1);
    }

    public function test_conteo_de_abiertos_respeta_alcance_propias(): void
    {
        $this->celda('Calidad', 'pedidos', 'editar', 'propias');
        $sofia = $this->sofia();
        $obra = $this->obra();
        $laura = $this->laura();
        $this->crear($laura, $this->carla(), $obra);
        $this->crear($sofia, $this->carla(), $obra);

        $this->actingAs($sofia);
        $this->assertSame([$obra->id => 1], $this->app->make(PedidosDeObra::class)->contarAbiertosPorObra([$obra->id]));
        $this->actingAs($laura);
        $this->assertSame([$obra->id => 2], $this->app->make(PedidosDeObra::class)->contarAbiertosPorObra([$obra->id]));
    }

    public function test_abiertos_de_la_obra_para_w22_con_propietario_o_sector(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $conPersona = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-20']);
        $enSector = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-25']);
        $this->accion($carla, $enSector, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")
            ->assertOk()
            ->assertJsonPath('data.0.id', $conPersona->id)
            ->assertJsonPath('data.0.propietario.nombre', 'Carla')
            ->assertJsonPath('data.1.id', $enSector->id)
            ->assertJsonPath('data.1.propietario', null)
            ->assertJsonPath('data.1.sector.nombre', 'Compras');
    }

    public function test_terminar_con_pedidos_nuevos_no_confirmados_devuelve_409_con_datos_reales(): void
    {
        $laura = $this->laura();
        $obra = $this->obra();
        $obra->update(['responsable_id' => $laura->id]);
        $primero = $this->crear($laura, $this->carla(), $obra);
        $this->crear($laura, $this->carla(), $obra);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'terminada', 'confirmo_pedidos_abiertos' => [$primero->id]])->assertConflict();
    }

    public function test_pedidos_para_recordar_trae_los_abiertos_que_vencen_ese_dia(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $manana = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-20']);
        $completado = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-20']);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-21']);

        $lista = $this->app->make(PedidosParaRecordar::class)->conEntregaEl(new DateTimeImmutable('2026-10-20'));

        $this->assertCount(1, $lista);
        $this->assertSame([$manana->id, $carla->id, $carla->sector_id, 'Reparación de losa tanque B', '2026-10-20'], [
            $lista[0]->pedidoId, $lista[0]->propietarioId, $lista[0]->sectorId, $lista[0]->obraNombre, $lista[0]->fechaEntrega,
        ]);
    }

    public function test_obra_elegible_para_pedidos_no_mira_el_presupuesto_por_rubro_y_admite_terminadas(): void
    {
        $terminada = $this->obra(ObraEstado::Terminada, 'Impermeabilización cubierta');

        $this->assertTrue($this->app->make(ObrasConsulta::class)->existe($terminada->id));
        $this->actingAs($this->laura())->getJson('/api/pedidos/obras-opciones?buscar=cubierta')
            ->assertOk()
            ->assertJsonPath('data.0.id', $terminada->id)
            ->assertJsonPath('data.0.estado', 'terminada');
    }

    public function test_selector_de_obras_pide_pedidos_editar_aunque_no_vea_obras(): void
    {
        $this->obra();
        $this->celda('Calidad', 'obras', 'sin_acceso');
        $this->actingAs($this->sofia())->getJson('/api/pedidos/obras-opciones')->assertOk()->assertJsonCount(1, 'data');

        $this->celda('Calidad', 'pedidos', 'ver');
        $this->actingAs($this->sofia())->getJson('/api/pedidos/obras-opciones')->assertForbidden();
    }
}
