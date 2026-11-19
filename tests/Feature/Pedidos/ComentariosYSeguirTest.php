<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Models\PedidoComentario;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LogicException;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-029 · HU-030 · F-30 · F-07 (W-09, W-28): detalle, comentarios y seguir. */
final class ComentariosYSeguirTest extends PedidosTestCase
{
    public function test_detalle_muestra_todo_el_pedido_y_los_botones_de_cada_uno(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla, $this->obra(), ['periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-11-06', 'aclaraciones' => 'Andamio tubular para unos 40 m².']);

        $this->actingAs($carla)->getJson("/api/pedidos/{$pedido->id}")
            ->assertOk()
            ->assertJsonPath('data.solicitante.sector.nombre', 'Ingeniería de proyectos')
            ->assertJsonPath('data.propietario.sector.nombre', 'Compras')
            ->assertJsonPath('data.periodo', ['inicio' => '2026-10-26', 'fin' => '2026-11-06'])
            ->assertJsonPath('data.aclaraciones', 'Andamio tubular para unos 40 m².')
            ->assertJsonPath('data.puede.tomar', true)
            ->assertJsonPath('data.puede.reabrir', false)
            ->assertJsonPath('data.puede.corregir_prioridad', false)
            ->assertJsonPath('data.puede.seguir', false);

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}")
            ->assertJsonPath('data.puede.tomar', false)
            ->assertJsonPath('data.puede.reasignar', true)
            ->assertJsonPath('data.puede.corregir_prioridad', true);
    }

    public function test_pedido_que_no_existe_dice_pedido_no_encontrado(): void
    {
        $this->actingAs($this->laura())->getJson('/api/pedidos/999999')->assertNotFound()->assertJsonPath('message', 'Pedido no encontrado.');
    }

    public function test_comentario_guarda_autor_y_fecha(): void
    {
        Event::fake([PedidoComentado::class]);
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);

        $this->actingAs($carla)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'Falta la cantidad de bolsas de cemento: decime cuántas van.'])
            ->assertCreated()
            ->assertJsonPath('data.autor', ['id' => $carla->id, 'nombre' => 'Carla'])
            ->assertJsonPath('data.created_at', '2026-10-19T10:00:00-03:00');
        Event::assertDispatched(PedidoComentado::class, fn (PedidoComentado $e): bool => $e->actorId === $carla->id && $e->texto === 'Falta la cantidad de bolsas de cemento: decime cuántas van.');
    }

    public function test_comentario_vacio_es_rechazado(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => ' '])
            ->assertUnprocessable()
            ->assertJsonPath('errors.texto.0', 'Escribí el comentario.');
    }

    public function test_quien_lo_ve_comenta_aunque_tenga_solo_ver(): void
    {
        $this->celda('Jefatura de Proyectos', 'pedidos', 'ver');
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($hernan)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => '30 bolsas.'])->assertCreated();
    }

    public function test_se_comenta_un_pedido_completado(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'Gracias.'])->assertCreated();
    }

    public function test_comentarios_no_se_editan_ni_se_borran(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());
        $id = $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'Hola.'])->json('data.id');
        $this->assertIsInt($id);
        $comentario = PedidoComentario::query()->findOrFail($id);

        $this->actingAs($laura)->deleteJson("/api/pedidos/{$pedido->id}/comentarios/{$id}")->assertStatus(404);
        $this->expectException(LogicException::class);
        $comentario->delete();
    }

    public function test_doble_envio_del_comentario_crea_uno_solo(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());
        $clave = (string) Str::uuid();

        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => '30 bolsas.'], ['Idempotency-Key' => $clave])->assertCreated();
        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => '30 bolsas.'], ['Idempotency-Key' => $clave])->assertCreated();

        $this->assertSame(1, PedidoComentario::query()->count());
    }

    public function test_comentar_un_pedido_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Calidad', 'pedidos', 'editar', 'propias');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->sofia())->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'x'])->assertNotFound();
    }

    public function test_seguir_un_pedido_de_otro_con_pedidos_ver(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver');
        $sofia = $this->sofia();
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($sofia)->postJson("/api/pedidos/{$pedido->id}/seguir")->assertOk()->assertJsonPath('data.sigo', true);
        $this->actingAs($sofia)->getJson('/api/pedidos/que-sigo')->assertJsonPath('data.0.id', $pedido->id);
        $this->actingAs($sofia)->getJson("/api/pedidos/{$pedido->id}")->assertJsonPath('data.puede.dejar_de_seguir', true);
    }

    public function test_no_se_sigue_un_pedido_propio(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->actingAs($laura)->postJson("/api/pedidos/{$pedido->id}/seguir")
            ->assertUnprocessable()
            ->assertJsonPath('errors.pedido.0', 'Ya recibís los avisos de este pedido.');
    }

    public function test_seguir_dos_veces_no_duplica(): void
    {
        $sofia = $this->sofia();
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($sofia)->postJson("/api/pedidos/{$pedido->id}/seguir")->assertOk();
        $this->actingAs($sofia)->postJson("/api/pedidos/{$pedido->id}/seguir")->assertOk();

        $this->assertSame(1, $pedido->seguidores()->count());
    }

    public function test_dejar_de_seguir(): void
    {
        $sofia = $this->sofia();
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['seguidores' => [$sofia->id]]);

        $this->actingAs($sofia)->deleteJson("/api/pedidos/{$pedido->id}/seguir")->assertOk()->assertJsonPath('data.sigo', false);
        $this->actingAs($sofia)->getJson('/api/pedidos/que-sigo')->assertJsonCount(0, 'data');
        $this->actingAs($sofia)->deleteJson("/api/pedidos/{$pedido->id}/seguir")->assertOk();
    }

    public function test_seguir_sin_acceso_a_pedidos_devuelve_404(): void
    {
        $this->celda('Calidad', 'pedidos', 'sin_acceso');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->sofia())->postJson("/api/pedidos/{$pedido->id}/seguir")->assertNotFound();
    }
}
