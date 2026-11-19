<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Application\Sectores\SacarSectorAction;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-058 · F-58 · F-59 (W-39, W-40) · DU-P09 · DU-Q02: personas y sectores con pedidos abiertos. */
final class PersonasYSectoresConPedidosTest extends PedidosTestCase
{
    private function gerencia(): User
    {
        return $this->persona('Hernán', 'Gerencia de Administración');
    }

    public function test_w39_avisa_los_pedidos_abiertos_de_la_persona_y_no_reasigna_nada(): void
    {
        $carla = $this->carla();
        $this->persona('Pablo', 'Compras');
        $laura = $this->laura();
        $this->crear($laura, $carla);
        $this->crear($laura, $carla);
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $gerencia = $this->gerencia();

        $this->actingAs($gerencia)->getJson("/api/usuarios/{$carla->id}")
            ->assertOk()
            ->assertJsonPath('data.pedidos_abiertos', 2)
            ->assertJsonPath('data.pedidos_en_el_sector', 0);
        $this->actingAs($gerencia)->patchJson("/api/usuarios/{$carla->id}", ['activo' => false])->assertOk();

        $this->assertSame(2, Pedido::query()->where('propietario_id', $carla->id)->where('estado', '!=', 'completada')->count());
        $this->actingAs($laura)->getJson('/api/pedidos?propietario_id='.$carla->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.propietario.activo', false);
    }

    public function test_w39_avisa_si_es_la_ultima_persona_activa_de_un_sector_con_pedidos_en_el_sector(): void
    {
        $carla = $this->carla();
        $laura = $this->laura();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();
        $gerencia = $this->gerencia();

        $this->actingAs($gerencia)->getJson("/api/usuarios/{$carla->id}")->assertJsonPath('data.pedidos_en_el_sector', 1);

        $this->persona('Pablo', 'Compras');
        $this->actingAs($gerencia)->getJson("/api/usuarios/{$carla->id}")->assertJsonPath('data.pedidos_en_el_sector', 0);
    }

    public function test_no_se_saca_un_sector_con_pedidos_en_el_sector_sin_persona(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();
        $carla->update(['activo' => false]);
        $compras = $this->sector('Compras');

        $this->actingAs($this->gerencia())->deleteJson("/api/sectores/{$compras->id}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.sector.0', 'Antes de sacar Compras, reasigná su pedido que está en el sector.');
        $this->assertDatabaseHas('sectores', ['id' => $compras->id, 'activo' => true]);
    }

    public function test_sector_con_pedidos_y_sin_usuarios_activos_se_archiva_y_los_pedidos_conservan_su_nombre(): void
    {
        $carla = $this->carla();
        $laura = $this->laura();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $carla->forceFill(['sector_id' => $this->sector('Calidad')->id])->save();
        $compras = $this->sector('Compras');

        $this->actingAs($this->gerencia())->deleteJson("/api/sectores/{$compras->id}")
            ->assertOk()
            ->assertJsonPath('data.resultado', SacarSectorAction::ARCHIVADO);
        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}")->assertOk()->assertJsonPath('data.sector.nombre', 'Compras');
        $this->assertSame(PedidoEstado::Completada, $pedido->refresh()->estado);
    }

    public function test_persona_inactiva_no_aparece_en_los_desplegables(): void
    {
        $carla = $this->carla();
        $carla->update(['activo' => false]);

        $nombres = array_column((array) $this->actingAs($this->laura())->getJson('/api/usuarios/opciones?sector_id='.$carla->sector_id)->assertOk()->json('data'), 'nombre');

        $this->assertNotContains('Carla', $nombres);
        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($carla))->assertUnprocessable();
    }

    public function test_sin_usuarios_permisos_ver_no_ve_los_pedidos_de_una_persona(): void
    {
        $carla = $this->carla();

        $this->actingAs($this->laura())->getJson("/api/usuarios/{$carla->id}")->assertForbidden();
    }
}
