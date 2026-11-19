<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\PedidoBloqueado;
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-032 · HU-033 · F-28 · F-29 (W-09, W-30): tomar, bloquear con motivo y retomar. */
final class TomarYBloquearTest extends PedidosTestCase
{
    public function test_propietario_toma_y_pasa_a_en_curso(): void
    {
        Event::fake([PedidoTomado::class]);
        $laura = $this->laura();
        $carla = $this->carla();
        $sofia = $this->sofia();
        $pedido = $this->crear($laura, $carla, null, ['seguidores' => [$sofia->id]]);

        $this->accion($carla, $pedido, 'tomar')->assertOk()->assertJsonPath('data.estado', 'en_curso');

        $actividad = Actividad::query()->where('accion', 'tomado')->sole();
        Event::assertDispatched(PedidoTomado::class, fn (PedidoTomado $e): bool => $e->actividadId === $actividad->id
            && $e->actorId === $carla->id && ! $e->desdeSector && $e->participantes->seguidorIds === [$sofia->id]
            && $e->foto->estado === 'en_curso');
    }

    public function test_quien_no_es_propietario_no_toma(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla());

        $this->accion($laura, $pedido, 'tomar')->assertForbidden()->assertJsonPath('message', 'Solo el propietario puede tomarlo.');
        $this->assertSame('no_iniciada', $pedido->refresh()->estado->value);
    }

    public function test_tomar_uno_en_curso_devuelve_409_sin_segundo_aviso(): void
    {
        Event::fake([PedidoTomado::class]);
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'tomar')->assertOk();

        $this->accion($carla, $pedido, 'tomar')
            ->assertConflict()
            ->assertJsonPath('data.estado', 'en_curso')
            ->assertJsonPath('message', 'Ya lo tomó Carla.');
        Event::assertDispatchedTimes(PedidoTomado::class, 1);
    }

    public function test_tomar_con_la_misma_idempotency_key_devuelve_la_misma_respuesta(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $clave = (string) Str::uuid();

        $this->actingAs($carla)->postJson("/api/pedidos/{$pedido->id}/tomar", [], ['Idempotency-Key' => $clave])->assertOk();
        $this->actingAs($carla)->postJson("/api/pedidos/{$pedido->id}/tomar", [], ['Idempotency-Key' => $clave])
            ->assertOk()
            ->assertHeader('Idempotent-Replayed');
        $this->assertSame(1, Actividad::query()->where('accion', 'tomado')->count());
    }

    public function test_persona_del_sector_toma_y_queda_propietaria(): void
    {
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $pedido = $this->pedidoEnElSector($carla);

        $this->accion($pablo, $pedido, 'tomar')
            ->assertOk()
            ->assertJsonPath('data.propietario.id', $pablo->id)
            ->assertJsonPath('data.estado', 'en_curso');
    }

    public function test_dos_toman_a_la_vez_el_segundo_recibe_409(): void
    {
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $pedido = $this->pedidoEnElSector($carla);

        $this->accion($carla, $pedido, 'tomar')->assertOk();
        $this->accion($pablo, $pedido, 'tomar')->assertConflict()->assertJsonPath('message', 'Ya lo tomó Carla.');
    }

    public function test_bloquear_exige_motivo(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'bloquear', ['motivo' => '  '])
            ->assertUnprocessable()
            ->assertJsonPath('errors.motivo.0', 'Contá qué lo traba.');
    }

    public function test_bloquear_desde_no_iniciada(): void
    {
        Event::fake([PedidoBloqueado::class]);
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $motivo = 'Falta el talle de botines de 3 personas de la cuadrilla.';

        $this->accion($carla, $pedido, 'bloquear', ['motivo' => $motivo])
            ->assertOk()
            ->assertJsonPath('data.estado', 'bloqueada')
            ->assertJsonPath('data.motivo_bloqueo', $motivo);
        Event::assertDispatched(PedidoBloqueado::class, fn (PedidoBloqueado $e): bool => $e->motivo === $motivo);
    }

    public function test_solo_el_propietario_bloquea(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->accion($this->sofia(), $pedido, 'bloquear', ['motivo' => 'x'])->assertForbidden();
    }

    public function test_bloquear_uno_completado_devuelve_409(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'x'])
            ->assertConflict()
            ->assertJsonPath('data.estado', 'completada')
            ->assertJsonPath('message', 'Está completado: si falta algo, reabrilo.');
    }

    public function test_retomar_vuelve_a_en_curso_y_no_genera_aviso(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);
        $this->accion($carla, $pedido, 'bloquear', ['motivo' => 'Esperando presupuesto'])->assertOk();
        $this->fingirEventos();

        $this->accion($carla, $pedido, 'retomar')
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_curso')
            ->assertJsonPath('data.motivo_bloqueo', null);
        Event::assertNothingDispatched();
        $this->assertSame('Esperando presupuesto', Actividad::query()->where('accion', 'retomado')->sole()->cambios['motivo_bloqueo']['antes'] ?? null);
    }

    public function test_retomar_uno_no_bloqueado_devuelve_409(): void
    {
        $carla = $this->carla();
        $pedido = $this->crear($this->laura(), $carla);

        $this->accion($carla, $pedido, 'retomar')->assertConflict()->assertJsonPath('data.estado', 'no_iniciada');
    }

    public function test_pedido_fuera_de_alcance_devuelve_404_en_las_transiciones(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $pedido = $this->crear($this->laura(), $this->carla());

        $this->accion($this->sofia(), $pedido, 'tomar')->assertNotFound()->assertJsonPath('message', 'Pedido no encontrado.');
    }

    private function pedidoEnElSector(User $deCompras): Pedido
    {
        $pedido = $this->crear($this->laura(), $deCompras);
        $this->accion($deCompras, $pedido, 'reasignar', ['sector_id' => $deCompras->sector_id])->assertOk();

        return $pedido->refresh();
    }
}
