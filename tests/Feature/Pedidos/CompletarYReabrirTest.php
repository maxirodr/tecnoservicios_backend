<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use App\Modules\Pedidos\Domain\Events\PedidoReabierto;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-034 · HU-036 · F-31 · F-65 (W-29, W-42, W-09): completar con comentario y comprobante, y reabrir. */
final class CompletarYReabrirTest extends PedidosTestCase
{
    public function test_completar_pedido_sin_comentario_es_rechazado(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'completar', [])->assertUnprocessable()->assertJsonPath('errors.comentario.0', 'Contá cómo se resolvió.');
    }

    public function test_completar_desde_no_iniciada_registra_que_no_se_tomo(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Entregado en obra, recibió el encargado.'])->assertOk();

        $this->actingAs($carla)->getJson("/api/pedidos/{$pedido->id}/recorrido")
            ->assertOk()
            ->assertJsonFragment(['que' => "Carla la completó sin tomarla: 'Entregado en obra, recibió el encargado.'"]);
    }

    public function test_completar_guarda_quien_y_cuando(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'completada')
            ->assertJsonPath('data.cierre.comentario', 'Listo.')
            ->assertJsonPath('data.cierre.completado_por.id', $carla->id)
            ->assertJsonPath('data.cierre.completado_at', '2026-10-19T10:00:00-03:00')
            ->assertJsonPath('data.vencido', false);
    }

    public function test_completar_desde_bloqueada_devuelve_409(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Esperando presupuesto'])->assertOk();

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])
            ->assertConflict()
            ->assertJsonPath('message', 'Primero retomala: está bloqueada.')
            ->assertJsonPath('data.estado', 'bloqueada');
    }

    public function test_completar_acepta_comprobantes_archivo_o_link(): void
    {
        Event::fake([PedidoCompletado::class]);
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $foto = $this->archivo($carla, 'comprobante_cierre');
        $link = $this->link($carla, 'comprobante_cierre', 'https://drive.example/remito-1234');

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Entregado.', 'comprobantes' => [$foto, $link]])
            ->assertOk()
            ->assertJsonCount(2, 'data.comprobantes')
            ->assertJsonPath('data.comprobantes.0.subido_por.id', $carla->id);
        Event::assertDispatched(PedidoCompletado::class, fn (PedidoCompletado $e): bool => $e->cantidadComprobantes === 2 && $e->comentario === 'Entregado.');
    }

    public function test_comprobante_de_otro_contexto_no_se_asocia(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $deObra = $this->link($carla, 'pedido');

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.', 'comprobantes' => [$deObra]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.comprobantes.0', 'Ese adjunto no se puede usar acá.');
        $this->assertSame('no_iniciada', $pedido->refresh()->estado->value);
    }

    public function test_solo_quien_completo_quita_un_comprobante(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $remito = $this->link($carla, 'comprobante_cierre');
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.', 'comprobantes' => [$remito]])->assertOk();

        $this->actingAs($laura)->deleteJson("/api/adjuntos/{$remito}")->assertForbidden();
        $this->actingAs($carla)->deleteJson("/api/adjuntos/{$remito}")->assertOk();
        $this->assertSame(1, Actividad::query()->where('accion', 'adjunto_quitado')->count());
    }

    public function test_completar_uno_completado_devuelve_409_sin_segundo_aviso(): void
    {
        Event::fake([PedidoCompletado::class]);
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertConflict()->assertJsonPath('message', 'Ya está completada.');
        Event::assertDispatchedTimes(PedidoCompletado::class, 1);
    }

    public function test_persona_del_sector_completa_y_queda_propietaria(): void
    {
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->accion($pablo, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk()->assertJsonPath('data.propietario.id', $pablo->id);
    }

    public function test_otro_no_completa(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->accion($this->sofia(), $pedido, 'completar', ['comentario' => 'Listo.'])->assertForbidden();
    }

    public function test_quien_lo_pidio_reabre_con_comentario_y_conserva_comprobantes(): void
    {
        Event::fake([PedidoReabierto::class, PedidoComentado::class]);
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->completado($laura, $carla, true);

        $this->accion($laura, $pedido, 'reabrir', ['comentario' => 'Faltaron 2 bolsas.'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_curso')
            ->assertJsonPath('data.propietario.id', $carla->id)
            ->assertJsonPath('data.cierre', null)
            ->assertJsonCount(1, 'data.comprobantes')
            ->assertJsonPath('data.comentarios.0.texto', 'Faltaron 2 bolsas.');
        Event::assertDispatched(PedidoReabierto::class, fn (PedidoReabierto $e): bool => $e->comentario === 'Faltaron 2 bolsas.' && $e->participantes->propietarioId === $carla->id);
        Event::assertNotDispatched(PedidoComentado::class);
    }

    public function test_reabrir_sin_comentario_es_rechazado(): void
    {
        $laura = $this->laura();
        $pedido = $this->completado($laura, $this->carla());

        $this->accion($laura, $pedido, 'reabrir', ['comentario' => ''])->assertUnprocessable()->assertJsonPath('errors.comentario.0', 'Contá qué falta.');
    }

    public function test_otro_no_reabre(): void
    {
        $pedido = $this->completado($this->laura(), $this->carla());

        $this->accion($this->sofia(), $pedido, 'reabrir', ['comentario' => 'x'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo quien la pidió puede reabrirla.');
    }

    public function test_reabrir_uno_abierto_devuelve_409(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->accion($laura, $pedido, 'reabrir', ['comentario' => 'x'])->assertConflict()->assertJsonPath('data.estado', 'no_iniciada');
    }

    public function test_reabrir_con_propietario_inactivo_exige_elegir_a_quien_pasarlo(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $diego = $this->diego();
        $pedido = $this->completado($laura, $carla);
        $carla->update(['activo' => false]);

        $this->accion($laura, $pedido, 'reabrir', ['comentario' => 'Faltaron 2 bolsas.'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.propietario_id.0', 'Carla está inactiva: elegí a quién se la pasás.');

        $this->accion($laura, $pedido, 'reabrir', ['comentario' => 'Faltaron 2 bolsas.', 'propietario_id' => $diego->id])
            ->assertOk()
            ->assertJsonPath('data.propietario.id', $diego->id)
            ->assertJsonPath('data.sector.id', $diego->sector_id);
    }

    public function test_sin_solicitante_reabre_quien_tiene_pedidos_editar(): void
    {
        $carla = $this->carla();
        $pedido = $this->completado($this->laura(), $carla);
        Pedido::query()->whereKey($pedido->id)->update(['solicitante_id' => null, 'carga_inicial' => true]);

        $this->accion($this->sofia(), $pedido, 'reabrir', ['comentario' => 'Falta el certificado.'])->assertOk();
    }

    private function completado(User $laura, User $carla, bool $conRemito = false): Pedido
    {
        $pedido = $this->crear($laura, $carla);
        $datos = ['comentario' => 'Entregado en obra.'];

        if ($conRemito) {
            $datos['comprobantes'] = [$this->link($carla, 'comprobante_cierre')];
        }

        $this->accion($carla, $pedido, 'completar', $datos)->assertOk();

        return $pedido->refresh();
    }
}
