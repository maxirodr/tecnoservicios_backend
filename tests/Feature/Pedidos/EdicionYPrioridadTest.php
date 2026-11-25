<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\Plazos;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-027 · HU-028 · HU-031 · F-25 · F-26 · F-27 · F-33 (W-08, W-32): fechas, prioridad, adjuntos y edición. */
final class EdicionYPrioridadTest extends PedidosTestCase
{
    public function test_fecha_de_pedido_es_hoy_en_argentina_por_defecto(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 01:30:00', 'UTC'));

        $pedido = $this->crear($this->laura(), $this->carla());

        $this->assertSame(self::HOY, $pedido->fecha_pedido?->toDateString());
    }

    public function test_fecha_de_pedido_se_puede_corregir_hacia_atras(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['fecha_pedido' => '2026-10-10']);

        $this->assertSame('2026-10-10', $pedido->fecha_pedido?->toDateString());
        $this->assertSame('normal', $pedido->prioridad->value);
    }

    public function test_fecha_de_pedido_futura_es_rechazada(): void
    {
        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla(), null, ['fecha_pedido' => '2026-10-20']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.fecha_pedido.0', 'La fecha en que se pidió no puede ser posterior a hoy.');
    }

    public function test_entrega_anterior_al_pedido_queda_urgente(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['fecha_entrega' => '2026-10-16']);

        $this->assertSame('urgente', $pedido->prioridad->value);
    }

    public function test_periodo_exige_inicio_y_fin(): void
    {
        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla(), null, ['periodo_inicio' => '2026-10-26']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.periodo_fin.0', 'Completá inicio y fin del período.');
    }

    public function test_fin_de_periodo_no_anterior_al_inicio(): void
    {
        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla(), null, ['periodo_inicio' => '2026-11-13', 'periodo_fin' => '2026-10-26']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.periodo_fin.0', 'El fin del período no puede ser anterior al inicio.');
    }

    public function test_periodo_no_se_compara_con_la_entrega(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['fecha_entrega' => '2026-10-16', 'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-11-06']);

        $this->assertSame('2026-11-06', $pedido->periodo_fin?->toDateString());
    }

    public function test_prioridad_se_calcula_con_los_plazos_de_w50(): void
    {
        Plazos::query()->update(['urgente_hasta_dias' => 1, 'prioritario_hasta_dias' => 3]);

        $this->actingAs($this->laura())->getJson('/api/pedidos/prioridad-sugerida?fecha_entrega=2026-10-23')
            ->assertOk()
            ->assertJsonPath('data', ['prioridad' => 'normal', 'dias' => 4]);
        $this->assertSame('normal', $this->crear($this->laura(), $this->carla())->prioridad->value);
    }

    public function test_cambiar_plazos_no_recalcula_pedidos_cargados(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla());
        Plazos::query()->update(['urgente_hasta_dias' => 1, 'prioritario_hasta_dias' => 3]);

        $this->assertSame('prioritario', $pedido->refresh()->prioridad->value);
    }

    public function test_elegir_la_misma_que_la_calculada_no_la_marca_manual(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['prioridad' => 'prioritario']);

        $this->assertFalse($pedido->prioridad_manual);
    }

    public function test_prioridad_manual_se_mantiene_al_cambiar_la_entrega_y_sugiere_la_calculada(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), null, ['prioridad' => 'urgente']);
        $this->assertTrue($pedido->prioridad_manual);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_entrega' => '2026-10-30'])
            ->assertOk()
            ->assertJsonPath('data.prioridad', 'urgente')
            ->assertJsonPath('data.prioridad_manual', true)
            ->assertJsonPath('data.prioridad_sugerida', ['prioridad' => 'normal', 'dias' => 11]);
    }

    public function test_usar_la_calculada_quita_la_marca_manual(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), null, ['prioridad' => 'urgente']);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['usar_prioridad_calculada' => true])
            ->assertOk()
            ->assertJsonPath('data.prioridad', 'prioritario')
            ->assertJsonPath('data.prioridad_manual', false);
    }

    public function test_solo_quien_lo_pidio_corrige_la_prioridad(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->actingAs($carla)->patchJson("/api/pedidos/{$pedido->id}", ['prioridad' => 'urgente'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo quien la pidió puede corregir la prioridad.');
    }

    public function test_cambiar_entrega_recalcula_con_plazos_vigentes_y_lo_registra(): void
    {
        Event::fake();
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), null, ['fecha_pedido' => '2026-10-12', 'fecha_entrega' => '2026-10-21']);
        $this->assertSame('normal', $pedido->prioridad->value);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_entrega' => '2026-10-23'])->assertOk();
        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_entrega' => '2026-10-15'])->assertOk()->assertJsonPath('data.prioridad', 'prioritario');

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido?solo=cambios")
            ->assertOk()
            ->assertJsonPath('data.0.que', 'Fecha de entrega: 23-10 → 15-10 (la prioridad pasó de Normal a Prioritario)')
            ->assertJsonPath('data.1.que', 'Fecha de entrega: 21-10 → 23-10 (la prioridad sigue Normal: 9 → 11 días)')
            ->assertJsonPath('data.2.que', 'Creó la tarea');
    }

    public function test_cambiar_fecha_de_pedido_recalcula(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_pedido' => '2026-10-01'])->assertJsonPath('data.prioridad', 'normal');
    }

    public function test_carga_inicial_sin_fecha_de_pedido_no_se_recalcula(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());
        Pedido::query()->whereKey($pedido->id)->update(['fecha_pedido' => null, 'carga_inicial' => true, 'prioridad' => 'normal']);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_entrega' => '2026-10-20'])->assertOk()->assertJsonPath('data.prioridad', 'normal');
    }

    public function test_editar_registra_valor_anterior_y_nuevo(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), null, ['tarea' => 'Alquiler de hidro']);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => 'Alquiler de hidrolavadora'])->assertOk();

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido?solo=cambios")
            ->assertJsonPath('data.0.que', "Tarea: 'Alquiler de hidro' → 'Alquiler de hidrolavadora'")
            ->assertJsonPath('data.0.quien.id', $laura->id);
    }

    public function test_editar_sin_cambios_no_registra(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => $pedido->tarea, 'fecha_entrega' => '2026-10-23'])->assertOk();

        $this->assertSame(0, Actividad::query()->where('accion', 'editado')->count());
    }

    public function test_editar_exige_pedidos_editar(): void
    {
        $this->celda('Compras', 'pedidos', 'ver');
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->actingAs($carla)->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => 'Otra'])->assertForbidden();
    }

    public function test_editar_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Calidad', 'pedidos', 'editar', 'propias');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->sofia())->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => 'Otra'])->assertNotFound();
    }

    public function test_editar_no_cambia_estado_propietario_sector_ni_solicitante(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $diego = $this->diego();
        $pedido = $this->crear($laura, $carla);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", [
            'estado' => 'completada', 'propietario_id' => $diego->id, 'sector_id' => $diego->sector_id, 'solicitante_id' => $diego->id,
        ])->assertOk();

        $pedido->refresh();
        $this->assertSame(['no_iniciada', $carla->id, $carla->sector_id, $laura->id], [$pedido->estado->value, $pedido->propietario_id, $pedido->sector_id, $pedido->solicitante_id]);
    }

    public function test_editar_cambia_la_obra_con_historial(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), $this->obra(nombre: 'Piso industrial nave 3'));
        $otra = $this->obra();

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['obra_id' => $otra->id])->assertOk()->assertJsonPath('data.obra.id', $otra->id);
        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['obra_id' => null])->assertOk()->assertJsonPath('data.obra', null);

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido?solo=cambios")
            ->assertJsonPath('data.0.que', 'Obra: Reparación de losa tanque B → Sin obra')
            ->assertJsonPath('data.1.que', 'Obra: Piso industrial nave 3 → Reparación de losa tanque B');
    }

    public function test_pedido_acepta_archivo_o_link_y_el_historial_los_informa_por_nombre_sin_url(): void
    {
        $laura = $this->laura();
        $drive = $this->link($laura, 'pedido');
        $plano = $this->archivo($laura, 'pedido');
        $pedido = $this->crear($laura, $this->carla(), null, ['adjuntos' => [$drive, $plano]]);

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['adjuntos' => [$drive]])->assertOk()->assertJsonCount(1, 'data.adjuntos');

        $historial = $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido?solo=cambios")->assertOk()->json();
        $this->assertIsArray($historial);
        $textos = implode("\n", array_column($historial['data'], 'que'));
        $this->assertStringContainsString('Quitó link o archivo: ', $textos);
        $this->assertStringNotContainsString('https://', $textos);
    }

    public function test_pedido_acepta_hasta_diez_links_o_archivos(): void
    {
        $laura = $this->laura();
        $ids = array_map(fn (int $i): int => $this->link($laura, 'pedido', "https://drive.example/link-{$i}"), range(1, 11));

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($this->carla(), null, ['adjuntos' => $ids]))->assertUnprocessable()->assertJsonValidationErrors('adjuntos');
        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($this->carla(), null, ['adjuntos' => array_slice($ids, 0, 10)]))->assertCreated()->assertJsonCount(10, 'data.adjuntos');
    }

    public function test_adjunto_de_otro_contexto_no_se_asocia_al_pedido(): void
    {
        $laura = $this->laura();
        $remito = $this->link($laura, 'comprobante_cierre');

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($this->carla(), null, ['adjuntos' => [$remito]]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.adjuntos.0', 'Ese adjunto no se puede usar acá.');
        $this->assertSame(0, Pedido::query()->count());
    }

    public function test_adjunto_de_pedido_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $laura = $this->laura();
        $plano = $this->archivo($laura, 'pedido');
        $this->crear($laura, $this->carla(), null, ['adjuntos' => [$plano]]);

        $this->actingAs($laura)->get("/api/adjuntos/{$plano}")->assertOk();
        $this->actingAs($this->sofia())->getJson("/api/adjuntos/{$plano}")->assertNotFound();
    }

    public function test_editar_un_completado_se_permite_y_editar_no_avisa(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->fingirEventos();

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['aclaraciones' => 'Corrijo el link.'])->assertOk();
        Event::assertNothingDispatched();
    }

    public function test_edicion_con_version_vieja_devuelve_409(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());
        $version = $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}")->json('data.updated_at');
        $this->travel(5)->seconds();
        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => 'Cambio uno'])->assertOk();

        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['tarea' => 'Cambio dos', 'updated_at' => $version])->assertConflict();
    }

    public function test_no_hay_ruta_para_borrar_pedidos(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->actingAs($laura)->deleteJson("/api/pedidos/{$pedido->id}")->assertStatus(405);
    }
}
