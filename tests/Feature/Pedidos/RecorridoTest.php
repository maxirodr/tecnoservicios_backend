<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Avisos\Contracts\AvisoEnviado;
use DateTimeImmutable;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-037 · F-34 · F-14 (W-09): recorrido del pedido. */
final class RecorridoTest extends PedidosTestCase
{
    public function test_recorrido_lista_todas_las_acciones_en_orden_con_quien_y_cuando(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->travel(1)->minutes();
        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->travel(1)->minutes();
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Esperando presupuesto'])->assertOk();
        $this->travel(1)->minutes();
        $this->accion($carla, $pedido, 'retomar')->assertOk();
        $this->travel(1)->minutes();
        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => '30 bolsas.'])->assertCreated();
        $this->travel(1)->minutes();
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Entregado.'])->assertOk();
        $this->travel(1)->minutes();
        $this->accion($laura, $pedido, 'reabrir', ['comentario' => 'Faltaron 2 bolsas.'])->assertOk();

        $respuesta = $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido")->assertOk();

        $this->assertSame([
            'Laura creó el pedido',
            'Carla lo tomó → En curso',
            "Carla lo bloqueó: 'Esperando presupuesto'",
            'Carla lo retomó → En curso',
            "Laura comentó: '30 bolsas.'",
            "Carla lo completó: 'Entregado.'",
            "Laura lo reabrió (falta algo): 'Faltaron 2 bolsas.'",
        ], array_column((array) $respuesta->json('data'), 'que'));
        $respuesta->assertJsonPath('data.1.quien', ['id' => $carla->id, 'nombre' => 'Carla'])
            ->assertJsonPath('data.0.cuando', '2026-10-19T10:00:00-03:00');
    }

    public function test_recorrido_incluye_los_avisos_que_mando_el_sistema(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->avisos->enviados[$pedido->id] = [
            new AvisoEnviado('asignado', [['id' => $carla->id, 'nombre' => 'Carla']], new DateTimeImmutable('2026-10-19 13:00:05+00:00')),
            new AvisoEnviado('vence_manana', [['id' => $carla->id, 'nombre' => 'Carla']], new DateTimeImmutable('2026-10-22 11:00:00+00:00')),
            new AvisoEnviado('completado_gente', [['id' => 3, 'nombre' => 'Hernán']], new DateTimeImmutable('2026-10-23 11:00:00+00:00')),
        ];

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido")
            ->assertJsonPath('data.1', ['cuando' => '2026-10-19T10:00:05-03:00', 'quien' => null, 'que' => 'Sistema avisó a Carla (asignado)'])
            ->assertJsonPath('data.2.que', 'Sistema: recordatorio, vence mañana')
            // Con palabras, no la clave del tipo (PU-607).
            ->assertJsonPath('data.3.que', 'Sistema avisó a Hernán (completado, a su jefe)');
    }

    public function test_recorrido_solo_cambios_es_lo_de_w32(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->travel(1)->minutes();
        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['con_quien' => 'Corralón Centro'])->assertOk();

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido?solo=cambios")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.que', "Con quién / para quién: 'Alquileres Andamios Oeste' → 'Corralón Centro'")
            ->assertJsonPath('data.1.que', 'Creó el pedido');
    }

    public function test_recorrido_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->sofia())->getJson("/api/pedidos/{$pedido->id}/recorrido")->assertNotFound();
    }

    public function test_quien_quedo_inactivo_sigue_con_su_nombre_en_el_recorrido(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $carla->update(['activo' => false]);

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido")->assertJsonPath('data.1.que', 'Carla lo tomó → En curso');
    }
}
