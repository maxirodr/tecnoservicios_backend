<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Equipos\ActualizarEquipoAction;
use App\Modules\Pedidos\Application\Equipos\QuitarEquipoAction;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Support\Str;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-050 · F-67 (W-49) · DU-Q03 · DU-P18 · ADR-014: equipos en reparación. Datos de la maqueta (ficticios). */
final class EquiposReparacionTest extends PedidosTestCase
{
    /**
     * @param  array<string, mixed>  $cambios
     */
    private function agregar(User $user, array $cambios = []): int
    {
        $id = $this->actingAs($user)->postJson('/api/equipos-reparacion', [
            'equipo' => 'Hidrolavadora', 'codigo' => 'HL-01', 'trabajo' => 'Pérdida en la bomba', 'taller' => 'Taller Hidráulica Sur',
            'fecha' => '2026-10-10', ...$cambios,
        ])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    public function test_equipos_editar_agrega_y_edita(): void
    {
        $carla = $this->carla();

        $this->actingAs($carla)->postJson('/api/equipos-reparacion', [
            'equipo' => 'Generador 5 kVA', 'codigo' => 'GE-04', 'trabajo' => 'No arranca', 'taller' => 'Electro Norte',
            'fecha' => '2026-10-12', 'notas' => 'Lista para retirar desde el 18-oct',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Agregamos el equipo.')
            ->assertJsonPath('data.equipo', 'Generador 5 kVA')
            ->assertJsonPath('data.codigo', 'GE-04')
            ->assertJsonPath('data.estado', 'no_iniciada')
            ->assertJsonPath('data.aprobacion', 'pendiente')
            ->assertJsonPath('data.fecha', '2026-10-12')
            ->assertJsonPath('data.inventario', null)
            ->assertJsonPath('data.updated_by.nombre', 'Carla');
        $id = EquipoReparacion::query()->valueOrFail('id');

        $this->actingAs($this->laura())->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'para_retiro', 'taller' => 'Electro Norte SRL'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'para_retiro')
            ->assertJsonPath('data.taller', 'Electro Norte SRL')
            ->assertJsonPath('data.trabajo', 'No arranca')
            ->assertJsonPath('data.updated_by.nombre', 'Laura');
    }

    public function test_equipos_ver_no_edita(): void
    {
        $id = $this->agregar($this->laura());
        $this->celda('Calidad', 'equipos_reparacion', 'ver');
        $sofia = $this->sofia();

        $this->actingAs($sofia)->getJson('/api/equipos-reparacion')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->actingAs($sofia)->postJson('/api/equipos-reparacion', ['equipo' => 'Mezcladora', 'trabajo' => 'Motor'])->assertForbidden();
        $this->actingAs($sofia)->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'en_curso'])->assertForbidden();
        $this->actingAs($sofia)->deleteJson("/api/equipos-reparacion/{$id}")->assertForbidden();
        $this->assertDatabaseHas('equipos_reparacion', ['id' => $id, 'estado' => 'no_iniciada']);
    }

    public function test_sin_acceso_no_ve_la_lista_ni_baja_sus_adjuntos(): void
    {
        $laura = $this->laura();
        $presupuesto = $this->archivo($laura, EquipoReparacion::CONTEXTO_PRESUPUESTO);
        $this->agregar($laura, ['presupuesto' => $presupuesto]);
        $this->celda('Calidad', 'equipos_reparacion', 'sin_acceso');
        $sofia = $this->sofia();

        $this->actingAs($sofia)->getJson('/api/equipos-reparacion')->assertForbidden();
        $this->actingAs($sofia)->get("/api/adjuntos/{$presupuesto}")->assertNotFound();
        $this->actingAs($laura)->get("/api/adjuntos/{$presupuesto}")->assertOk();
    }

    public function test_equipo_que_no_existe_devuelve_404(): void
    {
        $this->actingAs($this->laura())->patchJson('/api/equipos-reparacion/999', ['estado' => 'en_curso'])->assertNotFound();
        $this->actingAs($this->laura())->deleteJson('/api/equipos-reparacion/999')->assertNotFound();
    }

    public function test_equipo_exige_equipo_y_falla(): void
    {
        $laura = $this->laura();

        $this->actingAs($laura)->postJson('/api/equipos-reparacion', ['codigo' => 'HL-01'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.equipo.0', 'Escribí el equipo.')
            ->assertJsonPath('errors.trabajo.0', 'Contá la falla o el trabajo.');
        $id = $this->agregar($laura);
        $this->actingAs($laura)->patchJson("/api/equipos-reparacion/{$id}", ['equipo' => ''])->assertUnprocessable()->assertJsonValidationErrors(['equipo'], 'errors');
    }

    public function test_estado_y_aprobacion_solo_admiten_sus_valores(): void
    {
        $this->actingAs($this->laura())->postJson('/api/equipos-reparacion', ['equipo' => 'Hidrolavadora', 'trabajo' => 'Bomba', 'estado' => 'perdido', 'aprobacion' => 'quizas', 'fecha' => '10/10/2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estado', 'aprobacion', 'fecha'], 'errors');
    }

    public function test_estado_y_aprobacion_se_cambian_libremente(): void
    {
        $carla = $this->carla();
        $id = $this->agregar($carla, ['equipo' => 'Generador 5 kVA', 'trabajo' => 'No arranca']);

        $this->actingAs($carla)->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'completada'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'completada')
            ->assertJsonPath('data.aprobacion', 'pendiente');
        $this->actingAs($carla)->patchJson("/api/equipos-reparacion/{$id}", ['aprobacion' => 'bloqueado', 'estado' => 'no_iniciada'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'no_iniciada')
            ->assertJsonPath('data.aprobacion', 'bloqueado');
    }

    public function test_equipo_se_quita_solo_si_sigue_no_iniciada(): void
    {
        $carla = $this->carla();
        $repetida = $this->agregar($carla, ['equipo' => 'Mezcladora', 'codigo' => 'MZ-07']);
        $enCurso = $this->agregar($carla, ['equipo' => 'Mezcladora', 'codigo' => 'MZ-08', 'estado' => 'en_curso']);

        $this->actingAs($carla)->deleteJson("/api/equipos-reparacion/{$enCurso}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.estado.0', 'Ya está en curso: corregilo en lugar de quitarlo.');
        $this->actingAs($carla)->deleteJson("/api/equipos-reparacion/{$repetida}")->assertOk()->assertJsonPath('data', null);

        $this->assertDatabaseMissing('equipos_reparacion', ['id' => $repetida]);
        $this->assertDatabaseHas('equipos_reparacion', ['id' => $enCurso]);
        $this->assertTrue(Actividad::query()->where('sujeto_id', $repetida)->where('accion', QuitarEquipoAction::ACCION)->exists());
    }

    public function test_equipos_ordena_abiertos_por_fecha_y_completados_al_final(): void
    {
        $laura = $this->laura();
        $completado = $this->agregar($laura, ['fecha' => '2026-09-01', 'estado' => 'completada']);
        $sinFecha = $this->agregar($laura, ['fecha' => null]);
        $nuevo = $this->agregar($laura, ['fecha' => '2026-10-15']);
        $viejo = $this->agregar($laura, ['fecha' => '2026-10-01', 'estado' => 'bloqueada']);

        $ids = $this->actingAs($laura)->getJson('/api/equipos-reparacion')->assertOk()->assertJsonPath('meta.per_page', 50)->json('data.*.id');

        $this->assertSame([$viejo, $nuevo, $sinFecha, $completado], $ids);
        $this->actingAs($laura)->getJson('/api/equipos-reparacion?estado=completada')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $completado);
        $this->actingAs($laura)->getJson('/api/equipos-reparacion?estado=roto')->assertUnprocessable();
    }

    public function test_equipo_acepta_inventario_y_presupuesto_como_archivo_o_link(): void
    {
        $carla = $this->carla();
        $inventario = $this->link($carla, EquipoReparacion::CONTEXTO_INVENTARIO, 'https://drive.example/inventario/HL-01');
        $presupuesto = $this->archivo($carla, EquipoReparacion::CONTEXTO_PRESUPUESTO);
        $id = $this->agregar($carla, ['inventario' => $inventario, 'presupuesto' => $presupuesto]);

        $this->actingAs($carla)->getJson('/api/equipos-reparacion')
            ->assertJsonPath('data.0.inventario.id', $inventario)
            ->assertJsonPath('data.0.inventario.tipo', 'link')
            ->assertJsonPath('data.0.presupuesto.id', $presupuesto)
            ->assertJsonPath('data.0.presupuesto.tipo', 'archivo');

        $otro = $this->link($carla, EquipoReparacion::CONTEXTO_INVENTARIO, 'https://drive.example/inventario/HL-01-v2');
        $this->actingAs($carla)->patchJson("/api/equipos-reparacion/{$id}", ['inventario' => $otro, 'presupuesto' => null])
            ->assertOk()
            ->assertJsonPath('data.inventario.id', $otro)
            ->assertJsonPath('data.presupuesto', null);
        $this->assertDatabaseMissing('adjuntos', ['id' => $inventario]);
        $this->assertDatabaseMissing('adjuntos', ['id' => $presupuesto]);
    }

    public function test_adjunto_de_otro_contexto_o_ajeno_no_se_asocia_al_equipo(): void
    {
        $carla = $this->carla();
        $deUnPedido = $this->link($carla, 'pedido');
        $ajeno = $this->link($this->laura(), EquipoReparacion::CONTEXTO_INVENTARIO);

        $this->actingAs($carla)->postJson('/api/equipos-reparacion', ['equipo' => 'Hidrolavadora', 'trabajo' => 'Bomba', 'inventario' => $deUnPedido])
            ->assertUnprocessable()
            ->assertJsonPath('errors.inventario.0', 'Ese adjunto no se puede usar acá.');
        $this->actingAs($carla)->postJson('/api/equipos-reparacion', ['equipo' => 'Hidrolavadora', 'trabajo' => 'Bomba', 'inventario' => $ajeno])->assertUnprocessable();
        $this->assertSame(0, EquipoReparacion::query()->count());
        $this->assertTrue(Adjunto::query()->findOrFail($ajeno)->esTemporal());
    }

    public function test_cambio_de_equipo_queda_en_actividades_con_quien_y_antes_despues(): void
    {
        $carla = $this->carla();
        $laura = $this->laura();
        $id = $this->agregar($carla);

        $this->actingAs($laura)->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'en_curso', 'aprobacion' => 'aprobado', 'notas' => 'Presupuesto del taller aprobado el 11-oct'])->assertOk();
        $this->actingAs($laura)->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'en_curso'])->assertOk();

        $ediciones = Actividad::query()->where('sujeto_type', EquipoReparacion::MORPH)->where('sujeto_id', $id)->where('accion', ActualizarEquipoAction::ACCION)->get();
        $this->assertCount(1, $ediciones);
        $edicion = $ediciones->firstOrFail();
        $this->assertSame($laura->id, $edicion->user_id);
        $this->assertSame([
            'estado' => ['antes' => 'no_iniciada', 'despues' => 'en_curso'],
            'aprobacion' => ['antes' => 'pendiente', 'despues' => 'aprobado'],
            'notas' => ['antes' => null, 'despues' => 'Presupuesto del taller aprobado el 11-oct'],
        ], $edicion->cambios);
    }

    public function test_editar_con_version_vieja_devuelve_409(): void
    {
        $carla = $this->carla();
        $id = $this->agregar($carla);
        $version = $this->actingAs($carla)->getJson('/api/equipos-reparacion')->json('data.0.updated_at');
        $this->travel(5)->seconds();
        $this->actingAs($this->laura())->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'en_curso'])->assertOk();

        $this->actingAs($carla)->patchJson("/api/equipos-reparacion/{$id}", ['estado' => 'bloqueada', 'updated_at' => $version])->assertConflict();
        $this->assertDatabaseHas('equipos_reparacion', ['id' => $id, 'estado' => 'en_curso']);
    }

    public function test_doble_envio_del_alta_crea_un_solo_equipo_y_no_avisa(): void
    {
        $carla = $this->carla();
        $clave = (string) Str::uuid();
        $datos = ['equipo' => 'Hidrolavadora', 'trabajo' => 'Pérdida en la bomba'];

        $primero = $this->actingAs($carla)->postJson('/api/equipos-reparacion', $datos, ['Idempotency-Key' => $clave])->assertCreated()->json('data.id');
        $segundo = $this->actingAs($carla)->postJson('/api/equipos-reparacion', $datos, ['Idempotency-Key' => $clave])->assertCreated()->json('data.id');

        $this->assertSame($primero, $segundo);
        $this->assertSame(1, EquipoReparacion::query()->count());
        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_la_lista_no_hace_una_consulta_por_equipo(): void
    {
        $laura = $this->laura();
        $this->agregar($laura, ['inventario' => $this->link($laura, EquipoReparacion::CONTEXTO_INVENTARIO), 'presupuesto' => $this->archivo($laura, EquipoReparacion::CONTEXTO_PRESUPUESTO)]);
        $this->actingAs($laura)->getJson('/api/equipos-reparacion')->assertOk();
        $uno = $this->consultasDe(fn () => $this->actingAs($laura)->getJson('/api/equipos-reparacion')->assertOk());

        foreach (['Pablo', 'Marta', 'Juan'] as $nombre) {
            $persona = $this->persona($nombre, 'Compras');
            $this->agregar($persona, ['presupuesto' => $this->archivo($persona, EquipoReparacion::CONTEXTO_PRESUPUESTO)]);
        }

        $this->assertLessThanOrEqual($uno, $this->consultasDe(fn () => $this->actingAs($laura)->getJson('/api/equipos-reparacion')->assertJsonCount(4, 'data')));
    }
}
