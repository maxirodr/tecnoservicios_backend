<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Avisos\Application\DestinatariosDeAsignacion;
use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Avisos\Infrastructure\AvisosDelPedidoEnLaBase;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/**
 * Pedidos y Avisos juntos, sin dobles: la acción real dispara el evento y el listener real crea el aviso
 * (avisos.md RN-AVI-01, -05; criterio de aceptación global n.º 3). Datos ficticios.
 */
final class AvisosDePuntaAPuntaTest extends PedidosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // La base registra Avisos en memoria: acá van los de verdad.
        $this->app->forgetInstance(AvisosDelPedido::class);
        $this->app->forgetInstance(DestinatariosDeAviso::class);
        $this->app->bind(AvisosDelPedido::class, AvisosDelPedidoEnLaBase::class);
        $this->app->bind(DestinatariosDeAviso::class, DestinatariosDeAsignacion::class);
        Mail::fake();
    }

    public function test_crear_un_pedido_le_avisa_al_propietario_en_la_campana_y_por_mail(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla))
            ->assertCreated()
            ->assertJsonPath('data.avisados.0.nombre', 'Carla');

        $this->assertSame(['asignado'], $this->tiposDe($carla));
        $this->assertSame([], $this->tiposDe($laura));
        $this->actingAs($carla)->getJson('/api/avisos/no-leidos/contador')->assertOk()->assertJsonPath('data.no_leidos', 1);
        Mail::assertQueuedCount(1);
    }

    public function test_tomar_avisa_a_quien_lo_pidio_y_retomar_o_editar_no_avisan(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);

        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Falta el talle de botines.'])->assertOk();
        $this->accion($carla, $pedido, 'retomar')->assertOk();
        $this->actingAs($laura)->patchJson("/api/pedidos/{$pedido->id}", ['fecha_entrega' => '2026-10-30'])->assertOk();

        $this->assertSame(['tomado', 'bloqueado'], $this->tiposDe($laura));
    }

    public function test_una_accion_que_falla_no_genera_aviso(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Falta el talle de botines.'])->assertOk();
        $antes = Aviso::query()->count();

        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Entregado.'])->assertConflict();

        $this->assertSame($antes, Aviso::query()->count());
    }

    public function test_el_recorrido_dice_a_quien_aviso_el_sistema(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla);

        $this->actingAs($laura)->getJson("/api/pedidos/{$pedido->id}/recorrido")
            ->assertOk()
            ->assertSee('Carla');
        $this->assertNotSame([], $this->app->make(AvisosDelPedido::class)->enviadosDe($pedido->id));
    }

    public function test_el_recordatorio_sale_para_un_pedido_real_que_vence_manana(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-20']);

        $this->command('avisos:recordatorios')->assertSuccessful()->run();
        $this->command('avisos:recordatorios')->assertSuccessful()->run();

        $this->assertSame(['asignado', 'vence_manana'], $this->tiposDe($carla));
        $recordatorio = Aviso::query()->where('user_id', $carla->id)->latest('id')->firstOrFail();
        // Con el id, el aviso lleva a la obra (no solo la nombra).
        $this->assertSame(['id' => $obra->id, 'nombre_trabajo' => 'Reparación de losa tanque B'], $recordatorio->datos['obra']);
    }

    /** @return list<string> */
    private function tiposDe(User $user): array
    {
        return array_values(Aviso::query()->where('user_id', $user->id)->orderBy('id')->get()
            ->map(fn (Aviso $aviso): string => $aviso->tipo->value)->all());
    }
}
