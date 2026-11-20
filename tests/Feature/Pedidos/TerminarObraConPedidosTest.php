<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-056 · F-21 (W-22) · DU-12 · DU-P12: los pedidos abiertos al pasar la obra a Terminada. */
final class TerminarObraConPedidosTest extends PedidosTestCase
{
    public function test_w22_ofrece_completar_solo_al_propietario_y_reasignar_al_propietario_o_a_quien_lo_pidio(): void
    {
        $diego = $this->diego();
        $carla = $this->carla();
        $laura = $this->laura();
        $obra = $this->obra();
        $obra->update(['responsable_id' => $diego->id]);
        $deCarla = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-20']);
        $loPidioDiego = $this->crear($diego, $carla, $obra, ['fecha_entrega' => '2026-10-21']);
        $deDiego = $this->crear($laura, $diego, $obra, ['fecha_entrega' => '2026-10-22']);

        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $deCarla->id)
            ->assertJsonPath('data.0.puede', ['completar' => false, 'reasignar' => false])
            ->assertJsonPath('data.1.id', $loPidioDiego->id)
            ->assertJsonPath('data.1.puede', ['completar' => false, 'reasignar' => true])
            ->assertJsonPath('data.2.id', $deDiego->id)
            ->assertJsonPath('data.2.puede', ['completar' => true, 'reasignar' => true]);
    }

    public function test_w22_no_ofrece_completar_un_bloqueado_ni_reasignar_sin_pedidos_editar(): void
    {
        $carla = $this->carla();
        $obra = $this->obra();
        $pedido = $this->crear($this->laura(), $carla, $obra);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Esperando presupuesto.'])->assertOk();

        $this->actingAs($carla)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertJsonPath('data.0.puede', ['completar' => false, 'reasignar' => true]);

        $this->celda('Compras', 'pedidos', 'ver');
        $this->actingAs($carla)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertJsonPath('data.0.puede.reasignar', false);
    }

    public function test_w22_en_el_sector_ofrece_completar_a_las_personas_del_sector(): void
    {
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $obra = $this->obra();
        $pedido = $this->crear($this->laura(), $carla, $obra);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->actingAs($pablo)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")
            ->assertJsonPath('data.0.propietario', null)
            ->assertJsonPath('data.0.puede', ['completar' => true, 'reasignar' => true]);
    }

    public function test_pasar_a_terminada_con_pedidos_abiertos_no_frena_y_siguen_en_mis_tareas(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $obra->update(['responsable_id' => $laura->id]);
        $pedido = $this->crear($laura, $carla, $obra);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'terminada', 'confirmo_pedidos_abiertos' => [$pedido->id]])
            ->assertOk()
            ->assertJsonPath('data.estado', ObraEstado::Terminada->value);
        $this->actingAs($carla)->getJson('/api/pedidos/mis-tareas')->assertJsonPath('data.0.id', $pedido->id);
        $this->actingAs($laura)->getJson("/api/pedidos?obra_id={$obra->id}")->assertJsonPath('data.0.id', $pedido->id);
    }

    public function test_pedido_cargado_mientras_tanto_devuelve_409_con_la_lista_y_sus_accesos(): void
    {
        $laura = $this->laura();
        $obra = $this->obra();
        $obra->update(['responsable_id' => $laura->id]);
        $primero = $this->crear($laura, $this->carla(), $obra);
        $nuevo = $this->crear($laura, $laura, $obra);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'terminada', 'confirmo_pedidos_abiertos' => [$primero->id]])
            ->assertConflict()
            ->assertJsonCount(2, 'errors.pedidos_abiertos')
            ->assertJsonPath('errors.pedidos_abiertos.1.id', $nuevo->id)
            ->assertJsonPath('errors.pedidos_abiertos.1.puede.completar', true);
        $this->assertSame(ObraEstado::EnEjecucion, $obra->refresh()->estado);
    }

    public function test_sin_obras_ver_no_ve_la_lista_de_w22(): void
    {
        $obra = $this->obra();
        $this->celda('Calidad', 'obras', 'sin_acceso');

        $this->actingAs($this->sofia())->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertForbidden();
    }
}
