<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-042 · HU-043 · HU-054 · F-35 · F-36 · F-17 (W-24, W-25, pestaña Pedidos de W-07). Hoy: lunes 19-oct. */
final class TodosLosPedidosTest extends PedidosTestCase
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return list<int>
     */
    private function ids(User $user, array $filtros = []): array
    {
        $ids = $this->actingAs($user)->getJson('/api/pedidos?'.http_build_query($filtros))->assertOk()->json('data.*.id');
        $this->assertIsArray($ids);

        return array_values(array_map(static fn (mixed $id): int => is_int($id) ? $id : 0, $ids));
    }

    public function test_alcance_todas_ve_la_tabla_con_las_columnas_de_w24(): void
    {
        $laura = $this->laura();
        $pedido = $this->crear($laura, $this->carla(), $this->obra());

        $this->actingAs($this->sofia())->getJson('/api/pedidos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pedido->id)
            ->assertJsonPath('data.0.obra.nombre_trabajo', 'Reparación de losa tanque B')
            ->assertJsonPath('data.0.sector.nombre', 'Compras')
            ->assertJsonPath('data.0.propietario.nombre', 'Carla')
            ->assertJsonPath('data.0.solicitante.nombre', 'Laura')
            ->assertJsonPath('data.0.fecha_pedido', self::HOY)
            ->assertJsonPath('data.0.fecha_entrega', '2026-10-23')
            ->assertJsonPath('data.0.prioridad', 'prioritario')
            ->assertJsonPath('data.0.estado', 'no_iniciada')
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_sin_acceso_a_pedidos_devuelve_403(): void
    {
        $this->celda('Calidad', 'pedidos', 'sin_acceso');

        $this->actingAs($this->sofia())->getJson('/api/pedidos')->assertForbidden()->assertJsonPath('data', null);
        $this->actingAs($this->sofia())->get('/api/pedidos/exportar')->assertForbidden();
    }

    public function test_sin_sesion_da_401(): void
    {
        $this->getJson('/api/pedidos')->assertUnauthorized();
    }

    public function test_estado_por_defecto_es_abiertos(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $abierto = $this->crear($laura, $carla);
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->assertSame([$abierto->id], $this->ids($laura));
        $this->assertEqualsCanonicalizing([$abierto->id, $completado->id], $this->ids($laura, ['estado' => 'todos']));
        $this->assertSame([$completado->id], $this->ids($laura, ['estado' => 'completada']));
    }

    public function test_filtros_se_combinan(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $sofia = $this->sofia();
        $obra = $this->obra();
        $deObra = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-20']);
        $sinObra = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-30']);
        $deSofia = $this->crear($carla, $sofia, $obra, ['fecha_entrega' => '2026-11-10', 'prioridad' => 'urgente']);
        $enSector = $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-16']);
        $this->accion($carla, $enSector, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();

        $this->assertSame([$enSector->id, $deObra->id, $deSofia->id], $this->ids($laura, ['obra_id' => $obra->id]));
        $this->assertSame([$sinObra->id], $this->ids($laura, ['obra_id' => 'sin_obra']));
        $this->assertSame([$deSofia->id], $this->ids($laura, ['sector_id' => $sofia->sector_id]));
        $this->assertSame([$deObra->id, $sinObra->id], $this->ids($laura, ['propietario_id' => $carla->id]));
        $this->assertSame([$enSector->id], $this->ids($laura, ['propietario_id' => 'en_sector']));
        $this->assertSame([$deSofia->id], $this->ids($laura, ['solicitante_id' => $carla->id]));
        $this->assertSame([$sinObra->id], $this->ids($laura, ['prioridad' => 'normal']));
        $this->assertSame([$enSector->id], $this->ids($laura, ['vencidos' => 1]));
        $this->assertSame([$deObra->id, $sinObra->id], $this->ids($laura, ['entrega_desde' => '2026-10-20', 'entrega_hasta' => '2026-10-30']));
        $this->assertSame([$deObra->id], $this->ids($laura, ['obra_id' => $obra->id, 'propietario_id' => $carla->id, 'entrega_hasta' => '2026-10-30']));
    }

    public function test_solo_vencidos_excluye_los_que_vencen_hoy(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $vencido = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $this->crear($laura, $carla, null, ['fecha_entrega' => self::HOY]);

        $this->assertSame([$vencido->id], $this->ids($laura, ['vencidos' => 1]));
    }

    public function test_orden_por_entrega_por_defecto(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $tarde = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-30']);
        $pronto = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-20']);
        $mismoDiaMasNuevo = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-30']);

        $this->assertSame([$pronto->id, $mismoDiaMasNuevo->id, $tarde->id], $this->ids($laura));
        $this->assertSame([$mismoDiaMasNuevo->id, $tarde->id, $pronto->id], $this->ids($laura, ['orden' => 'fecha_entrega', 'dir' => 'desc']));
    }

    public function test_orden_por_obra_y_por_fecha_de_pedido(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $nave = $this->crear($laura, $carla, $this->obra(nombre: 'Piso industrial nave 3'), ['fecha_pedido' => '2026-10-10']);
        $losa = $this->crear($laura, $carla, $this->obra(), ['fecha_pedido' => '2026-10-15']);
        $sinObra = $this->crear($laura, $carla, null, ['fecha_pedido' => '2026-10-01']);

        $this->assertSame([$nave->id, $losa->id, $sinObra->id], $this->ids($laura, ['orden' => 'obra']));
        $this->assertSame([$sinObra->id, $losa->id, $nave->id], $this->ids($laura, ['orden' => 'obra', 'dir' => 'desc']));
        $this->assertSame([$sinObra->id, $nave->id, $losa->id], $this->ids($laura, ['orden' => 'fecha_pedido']));
    }

    public function test_contador_muestra_filtrados_de_visibles(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $this->crear($laura, $carla, $this->obra());
        $this->crear($laura, $carla);
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->actingAs($laura)->getJson('/api/pedidos?obra_id=sin_obra')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.total_visibles', 3);
    }

    public function test_alcance_propias_muestra_solo_lo_que_puede_ver_en_la_tabla_y_los_totales(): void
    {
        $this->celda('Calidad', 'pedidos', 'editar', 'propias');
        $sofia = $this->sofia();
        $laura = $this->laura();
        $carla = $this->carla();
        $loPidio = $this->crear($sofia, $carla);
        $loSigue = $this->crear($laura, $carla, null, ['seguidores' => [$sofia->id]]);
        $this->crear($laura, $carla);

        $this->assertEqualsCanonicalizing([$loPidio->id, $loSigue->id], $this->ids($sofia));
        $this->actingAs($sofia)->getJson('/api/pedidos')->assertJsonPath('meta.total_visibles', 2);
    }

    public function test_servicios_generales_lista_los_sin_obra_abiertos(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $varias = $this->crear($laura, $carla, null, ['tarea' => 'Curso de trabajo en altura · obras Piso nave 3 y Losa tanque B']);
        $this->crear($laura, $carla, $this->obra());
        $cerrado = $this->crear($laura, $carla);
        $this->accion($carla, $cerrado, 'completar', ['comentario' => 'Cargado por error'])->assertOk();

        $this->assertSame([$varias->id], $this->ids($laura, ['obra_id' => 'sin_obra']));
    }

    public function test_pestana_pedidos_de_la_obra_respeta_el_alcance(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $sofia = $this->sofia();
        $laura = $this->laura();
        $obra = $this->obra();
        $suyo = $this->crear($laura, $sofia, $obra);
        $ajeno = $this->crear($laura, $this->carla(), $obra);
        $this->crear($laura, $sofia, $this->obra(nombre: 'Piso industrial nave 3'));

        $this->assertSame([$suyo->id], $this->ids($sofia, ['obra_id' => $obra->id]));
        $this->assertEqualsCanonicalizing([$suyo->id, $ajeno->id], $this->ids($laura, ['obra_id' => $obra->id, 'estado' => 'todos']));
    }

    public function test_filtros_invalidos_devuelven_422_por_campo(): void
    {
        $this->actingAs($this->laura())->getJson('/api/pedidos?entrega_desde=2026-10-30&entrega_hasta=2026-10-20&estado=perdido&prioridad=altisima&orden=tarea&propietario_id=nadie&obra_id=0')
            ->assertUnprocessable()
            ->assertJsonPath('errors.entrega_hasta.0', 'La entrega "hasta" no puede ser anterior a "desde".')
            ->assertJsonValidationErrors(['estado', 'prioridad', 'orden', 'propietario_id', 'obra_id'], 'errors');
    }

    public function test_paginado_de_a_50_sin_perder_los_filtros(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $primero = $this->crear($laura, $carla);
        Pedido::query()->insert(array_fill(0, 50, [
            'tarea' => 'Envío de EPP a obra', 'sector_id' => $carla->sector_id, 'propietario_id' => $carla->id,
            'solicitante_id' => $laura->id, 'fecha_pedido' => self::HOY, 'fecha_entrega' => '2026-11-30',
            'prioridad' => 'normal', 'estado' => 'no_iniciada', 'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->actingAs($laura)->getJson('/api/pedidos?propietario_id='.$carla->id.'&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 51)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertSame($primero->id, $this->ids($laura, ['propietario_id' => $carla->id])[0]);
    }

    public function test_la_lista_no_hace_una_consulta_por_pedido(): void
    {
        $laura = $this->laura();
        $this->crear($laura, $this->carla(), $this->obra());
        $this->actingAs($laura)->getJson('/api/pedidos')->assertOk();
        $una = $this->consultasDe(fn () => $this->actingAs($laura)->getJson('/api/pedidos')->assertOk());

        foreach (['Pablo', 'Marta', 'Juan'] as $nombre) {
            $this->crear($laura, $this->persona($nombre, 'Calidad'), $this->obra(nombre: "Obra de {$nombre}"), ['seguidores' => [$this->sofia()->id]]);
        }

        $this->assertSame($una, $this->consultasDe(fn () => $this->actingAs($laura)->getJson('/api/pedidos')->assertOk()->assertJsonCount(4, 'data')));
    }
}
