<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-035 · F-32 (W-31): reasignar a otra persona o dejarlo en el sector. */
final class ReasignarTest extends PedidosTestCase
{
    public function test_propietario_reasigna_a_otra_persona_y_cambia_el_sector(): void
    {
        Event::fake([PedidoReasignado::class, PedidoComentado::class]);
        $carla = $this->carla();
        $diego = $this->diego();
        $pedido = $this->crear($this->laura(), $carla, null, ['tarea' => 'Compra de discos de corte diamantados']);

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $diego->id, 'comentario' => 'Diego tiene la cuenta con el proveedor de discos.'])
            ->assertOk()
            ->assertJsonPath('data.propietario.id', $diego->id)
            ->assertJsonPath('data.sector.nombre', 'Jefatura de Administración')
            ->assertJsonPath('data.estado', 'no_iniciada')
            ->assertJsonPath('data.avisados', [['id' => $diego->id, 'nombre' => 'Diego']]);

        Event::assertDispatched(PedidoReasignado::class, fn (PedidoReasignado $e): bool => $e->propietarioAnteriorId === $carla->id
            && ! $e->aSector && $e->comentario === 'Diego tiene la cuenta con el proveedor de discos.');
        Event::assertNotDispatched(PedidoComentado::class);
    }

    public function test_comentario_de_la_reasignacion_queda_en_el_hilo(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $this->diego()->id, 'comentario' => 'Diego tiene la cuenta.'])
            ->assertJsonPath('data.comentarios.0.texto', 'Diego tiene la cuenta.')
            ->assertJsonPath('data.comentarios.0.autor.id', $carla->id);
    }

    public function test_solicitante_reasigna(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->accion($laura, $pedido, 'reasignar', ['propietario_id' => $this->diego()->id])->assertOk();
    }

    public function test_tercero_no_reasigna(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->accion($this->sofia(), $pedido, 'reasignar', ['propietario_id' => $this->diego()->id])->assertForbidden();
    }

    public function test_reasignar_a_una_persona_mantiene_el_estado(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Esperando presupuesto'])->assertOk();

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $this->diego()->id])
            ->assertJsonPath('data.estado', 'bloqueada')
            ->assertJsonPath('data.motivo_bloqueo', 'Esperando presupuesto');
    }

    public function test_dejarlo_en_el_sector_vuelve_a_no_iniciada_sin_propietario(): void
    {
        Event::fake([PedidoReasignado::class]);
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $marta = $this->persona('Marta', 'Compras');
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'tomar')->assertOk();

        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])
            ->assertOk()
            ->assertJsonPath('data.propietario', null)
            ->assertJsonPath('data.estado', 'no_iniciada')
            ->assertJsonPath('data.avisados', [['id' => $pablo->id, 'nombre' => 'Pablo'], ['id' => $marta->id, 'nombre' => 'Marta']]);
        Event::assertDispatched(PedidoReasignado::class, fn (PedidoReasignado $e): bool => $e->aSector && $e->participantes->propietarioId === null);

        $this->actingAs($carla)->getJson("/api/pedidos/{$pedido->id}/recorrido")
            ->assertJsonFragment(['que' => 'Carla la reasignó de Carla a Compras (sector) → No iniciada']);
    }

    public function test_no_se_deja_en_un_sector_sin_usuarios_activos(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $this->sector('Pañol y Logística')->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.sector_id.0', 'Pañol y Logística no tiene usuarios activos: elegí una persona.');
    }

    public function test_no_se_reasigna_a_quien_ya_es_el_propietario(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $carla->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.propietario_id.0', 'Ya es el propietario.');
    }

    public function test_reasignar_completado_devuelve_409(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $this->diego()->id])
            ->assertConflict()
            ->assertJsonPath('message', 'Está completada: si falta algo, reabrila.');
    }

    public function test_reasignar_pide_pedidos_editar(): void
    {
        $this->celda('Compras', 'pedidos', 'ver');
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'reasignar', ['propietario_id' => $this->diego()->id])->assertForbidden();
    }

    public function test_pedido_en_el_sector_aparece_en_mis_tareas_de_todo_el_sector(): void
    {
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->actingAs($pablo)->getJson('/api/pedidos/mis-tareas')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pedido->id)
            ->assertJsonPath('data.0.propietario', null);
    }
}
