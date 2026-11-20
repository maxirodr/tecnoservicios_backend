<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Support\Carbon;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-038 · HU-039 · F-04 · F-05 (W-01, W-27, W-41): Mis tareas, Pedidos que pedí e Inicio. Hoy: lunes 19-oct. */
final class InicioTest extends PedidosTestCase
{
    public function test_mis_tareas_muestra_solo_mis_abiertos_y_los_de_mi_sector_sin_propietario(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $mio = $this->crear($laura, $carla);
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $deOtro = $this->crear($laura, $pablo);
        $enSector = $this->crear($laura, $pablo);
        $this->accion($pablo, $enSector, 'reasignar', ['sector_id' => $pablo->sector_id])->assertOk();
        $this->crear($carla, $laura);

        $ids = array_column((array) $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')->assertOk()->json('data'), 'id');

        sort($ids);
        $this->assertSame([$mio->id, $enSector->id], $ids);
        $this->assertNotContains($deOtro->id, $ids);
    }

    public function test_pedido_vencido_aparece_marcado_en_mis_tareas(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $vencido = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $hoy = $this->crear($laura, $carla, null, ['fecha_entrega' => self::HOY]);
        $manana = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-20']);

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')
            ->assertJsonPath('data.0.id', $vencido->id)
            ->assertJsonPath('data.0.vencido', true)
            ->assertJsonPath('data.1.id', $hoy->id)
            ->assertJsonPath('data.1.vencido', false)
            ->assertJsonPath('data.2.id', $manana->id)
            ->assertJsonPath('data.2.vence_manana', true);
    }

    public function test_el_dia_se_corta_en_hora_de_argentina(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 02:00:00', 'UTC'));
        $carla = $this->carla();
        $this->crear($this->laura(), $carla, null, ['fecha_entrega' => self::HOY]);

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')->assertJsonPath('data.0.vencido', false);
    }

    public function test_mis_tareas_ordena_por_entrega_y_prioridad(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $normal = $this->crear($laura, $carla, null, ['fecha_pedido' => '2026-10-01', 'fecha_entrega' => '2026-10-25']);
        $urgente = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-25', 'prioridad' => 'urgente']);

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')
            ->assertJsonPath('data.0.id', $urgente->id)
            ->assertJsonPath('data.1.id', $normal->id);
    }

    public function test_mis_tareas_cuenta_abiertas_y_vencidas(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $this->crear($laura, $carla);

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas?buscar=nada-que-coincida')
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.abiertas', 2)
            ->assertJsonPath('meta.vencidas', 1);
    }

    public function test_mis_tareas_filtra_por_obra_estado_y_tarea(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $deObra = $this->crear($laura, $carla, $obra, ['tarea' => 'Envío de EPP a obra']);
        $sinObra = $this->crear($laura, $carla, null, ['tarea' => 'Alojamiento 6 personas']);
        $this->accion($carla, $sinObra, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->actingAs($carla)->getJson("/api/pedidos/mis-tareas?obra_id={$obra->id}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $deObra->id);
        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas?obra_id=sin_obra&estado=completada')->assertJsonPath('data.0.id', $sinObra->id);
        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas?estado=todos&buscar=EPP')->assertJsonCount(1, 'data');
        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas?estado=cualquiera')->assertUnprocessable();
    }

    public function test_fila_trae_periodo_obra_y_solicitante(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $this->crear($laura, $carla, $this->obra(), ['tarea' => 'Alojamiento 6 personas', 'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-11-06']);

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')
            ->assertJsonPath('data.0.periodo', ['inicio' => '2026-10-26', 'fin' => '2026-11-06'])
            ->assertJsonPath('data.0.obra.nombre_trabajo', 'Reparación de losa tanque B')
            ->assertJsonPath('data.0.solicitante', ['id' => $laura->id, 'nombre' => 'Laura'])
            ->assertJsonPath('data.0.propietario.activo', true);
    }

    public function test_pedidos_que_pedi_muestra_abiertos_por_defecto_y_todos_con_completados_al_final(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $completado = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $abierto = $this->crear($laura, $carla);
        $this->crear($this->sofia(), $carla);

        $this->actingAs($laura)->getJson('/api/pedidos/que-pedi')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $abierto->id);
        $this->actingAs($laura)->getJson('/api/pedidos/que-pedi?estado=todos')
            ->assertJsonPath('data.0.id', $abierto->id)
            ->assertJsonPath('data.1.id', $completado->id)
            ->assertJsonPath('data.1.ultimo_movimiento.texto', 'completado');
    }

    public function test_creado_sin_tomar_dice_nadie_lo_tomo_todavia(): void
    {
        $laura = $this->laura();
        $this->crear($laura, $this->carla());

        $this->actingAs($laura)->getJson('/api/pedidos/que-pedi')
            ->assertJsonPath('data.0.ultimo_movimiento', ['cuando' => '2026-10-19T10:00:00-03:00', 'texto' => 'creado; nadie lo tomó todavía']);
    }

    public function test_ultimo_movimiento_es_la_ultima_linea_del_recorrido(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->actingAs($carla)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'Pedido hecho a Corralón Centro, entregan el martes'])->assertCreated();

        $this->actingAs($laura)->getJson('/api/pedidos/que-pedi')
            ->assertJsonPath('data.0.ultimo_movimiento.texto', "Carla: 'Pedido hecho a Corralón Centro, entregan el martes'");
    }

    public function test_inicio_trae_los_contadores_de_las_pestanas(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $sofia = $this->sofia();
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16', 'seguidores' => [$sofia->id]]);
        $this->crear($laura, $carla);
        $this->crear($carla, $laura);

        $this->actingAs($carla)->getJson('/api/pedidos/inicio')
            ->assertOk()
            ->assertJsonPath('data', ['mis_tareas' => 2, 'vencidas' => 1, 'pedidos_que_pedi' => 1, 'tareas_que_sigo' => 0, 'tiene_gente' => false]);
        $this->actingAs($sofia)->getJson('/api/pedidos/inicio')->assertJsonPath('data.tareas_que_sigo', 1);
    }

    public function test_tiene_gente_con_un_activo_a_cargo_o_en_direccion(): void
    {
        $diego = $this->diego();
        $this->persona('Carla', 'Compras', ['jefe_id' => $diego->id]);
        $soloInactivo = $this->persona('Hernán', 'Jefatura de Proyectos');
        $this->persona('Mario', 'Compras', ['jefe_id' => $soloInactivo->id, 'activo' => false]);

        $this->actingAs($diego)->getJson('/api/pedidos/inicio')->assertJsonPath('data.tiene_gente', true);
        $this->actingAs($soloInactivo)->getJson('/api/pedidos/inicio')->assertJsonPath('data.tiene_gente', false);
        $this->actingAs($this->persona('Directora', 'Dirección'))->getJson('/api/pedidos/inicio')->assertJsonPath('data.tiene_gente', true);
    }

    public function test_si_le_sacan_el_acceso_despues_igual_ve_y_resuelve_lo_suyo(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->celda('Compras', 'pedidos', 'sin_acceso');

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')->assertJsonPath('data.0.id', $pedido->id);
        $this->actingAs($carla)->getJson("/api/pedidos/{$pedido->id}")->assertOk();
        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->assertSame('completada', Pedido::query()->findOrFail($pedido->id)->estado->value);
    }

    public function test_inicio_sin_sesion_devuelve_401(): void
    {
        $this->getJson('/api/pedidos/inicio')->assertUnauthorized();
        $this->getJson('/api/pedidos/mis-tareas')->assertUnauthorized();
    }

    public function test_mis_tareas_y_tareas_que_sigo_vienen_enteras_sin_cortar_en_50(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $primero = $this->crear($laura, $carla);
        $sofia = $this->sofia();
        $this->actingAs($sofia)->postJson("/api/pedidos/{$primero->id}/seguir")->assertSuccessful();
        foreach (range(1, 50) as $n) {
            $copia = $primero->replicate();
            $copia->tarea = "Pedido ficticio {$n}";
            $copia->save();
            $copia->seguidores()->attach($sofia->id);
        }

        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')
            ->assertOk()
            ->assertJsonCount(51, 'data')
            ->assertJsonPath('meta.abiertas', 51);
        $this->actingAs($sofia)->getJson('/api/pedidos/que-sigo')->assertOk()->assertJsonCount(51, 'data');
    }
}
