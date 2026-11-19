<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\Actividad;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-026 · F-24 · F-25 · F-38 (W-08, W-43): cargar un pedido. */
final class AltaPedidoTest extends PedidosTestCase
{
    public function test_cualquiera_con_pedidos_editar_crea_un_pedido(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla, $obra))
            ->assertCreated()
            ->assertJsonPath('data.estado', 'no_iniciada')
            ->assertJsonPath('data.solicitante.id', $laura->id)
            ->assertJsonPath('data.propietario.id', $carla->id)
            ->assertJsonPath('data.sector.nombre', 'Compras')
            ->assertJsonPath('data.obra.nombre_trabajo', 'Reparación de losa tanque B')
            ->assertJsonPath('data.prioridad', 'prioritario')
            ->assertJsonPath('data.dias_anticipacion', 4)
            ->assertJsonPath('data.fecha_pedido', self::HOY);
    }

    public function test_sin_pedidos_editar_no_crea(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver');

        $this->actingAs($this->sofia())->postJson('/api/pedidos', $this->alta($this->carla()))->assertForbidden();
        $this->assertSame(0, Pedido::query()->count());
    }

    public function test_solicitante_es_quien_carga_aunque_manden_otro(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();

        $pedido = $this->crear($laura, $carla, null, ['solicitante_id' => $carla->id]);

        $this->assertSame($laura->id, $pedido->solicitante_id);
    }

    public function test_pedido_nuevo_exige_tarea_obra_o_sin_obra_sector_propietario_y_entrega(): void
    {
        $this->actingAs($this->laura())->postJson('/api/pedidos', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tarea.0', 'Escribí la tarea.')
            ->assertJsonPath('errors.obra_id.0', 'Elegí la obra o \'Sin obra\'.')
            ->assertJsonPath('errors.sector_id.0', 'Elegí el sector.')
            ->assertJsonPath('errors.propietario_id.0', 'Elegí el propietario.')
            ->assertJsonPath('errors.fecha_entrega.0', 'Poné la fecha de entrega.');
    }

    public function test_propietario_tiene_que_ser_del_sector_elegido(): void
    {
        $carla = $this->carla();

        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($carla, null, ['sector_id' => $this->sector('Jefatura de Administración')->id]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.propietario_id.0', 'Carla no es de Jefatura de Administración.');
    }

    public function test_propietario_inactivo_es_rechazado(): void
    {
        $inactiva = $this->persona('Marta', 'Compras', ['activo' => false]);

        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($inactiva))
            ->assertUnprocessable()
            ->assertJsonPath('errors.propietario_id.0', 'Elegí una persona activa.');
    }

    public function test_propietario_solo_de_sectores_con_pedidos_ver(): void
    {
        $this->celda('Compras', 'pedidos', 'sin_acceso');

        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla()))
            ->assertUnprocessable()
            ->assertJsonPath('errors.propietario_id.0', 'Carla no tiene acceso a Pedidos: elegí otra persona.');
    }

    public function test_pedido_sin_obra_queda_en_servicios_generales(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['tarea' => 'Curso de trabajo en altura · obras Piso nave 3 y Losa tanque B']);

        $this->assertNull($pedido->obra_id);
    }

    public function test_pedido_a_obra_terminada_se_acepta(): void
    {
        $obra = $this->obra(ObraEstado::Terminada, 'Impermeabilización cubierta');

        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla(), $obra))
            ->assertCreated()
            ->assertJsonPath('data.obra.estado', 'terminada');
    }

    public function test_obra_que_no_existe_es_rechazada(): void
    {
        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($this->carla(), null, ['obra_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.obra_id.0', 'Elegí la obra o \'Sin obra\'.');
    }

    public function test_pedido_nace_no_iniciada_y_registra_que_se_creo(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), null, ['estado' => 'completada']);

        $this->assertSame('no_iniciada', $pedido->estado->value);
        $actividad = Actividad::query()->where('sujeto_type', 'pedido')->where('sujeto_id', $pedido->id)->sole();
        $this->assertSame('creado', $actividad->accion);
        $this->assertSame($laura->id, $actividad->user_id);
    }

    public function test_con_quien_es_texto_libre_y_no_valida_proveedor(): void
    {
        $pedido = $this->crear($this->laura(), $this->carla(), null, ['con_quien' => 'TSB']);

        $this->assertSame('TSB', $pedido->con_quien);
    }

    public function test_seguidores_al_crear_no_pueden_ser_el_solicitante_ni_el_propietario(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $sofia = $this->sofia();

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla, null, ['seguidores' => [$carla->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seguidores');

        $this->actingAs($laura)->postJson('/api/pedidos', $this->alta($carla, null, ['seguidores' => [$sofia->id]]))
            ->assertCreated()
            ->assertJsonPath('data.seguidores.0.nombre', 'Sofía');
    }

    public function test_respuesta_informa_a_quien_se_le_aviso(): void
    {
        $carla = $this->carla();

        $this->actingAs($this->laura())->postJson('/api/pedidos', $this->alta($carla))
            ->assertCreated()
            ->assertJsonPath('data.avisados', [['id' => $carla->id, 'nombre' => 'Carla']]);
    }

    public function test_alta_dispara_pedido_asignado_con_la_actividad_y_la_foto(): void
    {
        Event::fake([PedidoAsignado::class]);
        $laura = $this->laura();
        $carla = $this->carla();

        $pedido = $this->crear($laura, $carla, $this->obra());

        Event::assertDispatched(PedidoAsignado::class, function (PedidoAsignado $e) use ($pedido, $laura, $carla): bool {
            $actividad = Actividad::query()->where('accion', 'creado')->sole();

            return $e->pedidoId === $pedido->id && $e->actorId === $laura->id && $e->actividadId === $actividad->id
                && $e->participantes->propietarioId === $carla->id && $e->participantes->solicitanteId === $laura->id
                && $e->foto->obraNombre === 'Reparación de losa tanque B' && $e->foto->estado === 'no_iniciada'
                && $e->foto->fechaEntrega === '2026-10-23' && $e->foto->prioridad === 'prioritario';
        });
    }

    public function test_doble_envio_del_alta_crea_un_solo_pedido_y_un_solo_aviso(): void
    {
        Event::fake([PedidoAsignado::class]);
        $laura = $this->laura();
        $alta = $this->alta($this->carla());
        $clave = (string) Str::uuid();

        $primera = $this->actingAs($laura)->postJson('/api/pedidos', $alta, ['Idempotency-Key' => $clave])->assertCreated();
        $segunda = $this->actingAs($laura)->postJson('/api/pedidos', $alta, ['Idempotency-Key' => $clave])->assertCreated();

        $this->assertSame($primera->json('data.id'), $segunda->json('data.id'));
        $this->assertSame(1, Pedido::query()->count());
        Event::assertDispatchedTimes(PedidoAsignado::class, 1);
    }

    public function test_los_dos_sentidos_calidad_le_pide_a_compras(): void
    {
        $this->crear($this->sofia(), $this->carla());
        $this->crear($this->diego(), $this->laura());

        $this->assertSame(2, Pedido::query()->count());
    }
}
