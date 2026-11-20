<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/**
 * HU-040 · HU-041 · F-06 · F-12 · F-61 (W-26, W-04). Hoy: lunes 19-oct, la semana va del 19 al 25-oct.
 * Gerencia (Hernán) → Jefatura (Diego) → Carla; Laura es de otra rama.
 */
final class MiGenteTest extends PedidosTestCase
{
    /**
     * @return array{hernan: User, diego: User, carla: User, laura: User}
     */
    private function jerarquia(): array
    {
        $hernan = $this->persona('Hernán', 'Gerencia de Administración');
        $diego = $this->persona('Diego', 'Jefatura de Administración', ['jefe_id' => $hernan->id]);
        $carla = $this->persona('Carla', 'Compras', ['jefe_id' => $diego->id]);

        return ['hernan' => $hernan, 'diego' => $diego, 'carla' => $carla, 'laura' => $this->laura()];
    }

    public function test_tareas_de_mi_gente_agrupa_por_persona_e_incluye_al_jefe(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $vencido = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $otro = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-21']);
        $this->crear($laura, $diego);

        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.persona.nombre', 'Carla')
            ->assertJsonPath('data.0.persona.sector.nombre', 'Compras')
            ->assertJsonPath('data.0.persona.soy_yo', false)
            ->assertJsonPath('data.0.abiertas', 2)
            ->assertJsonPath('data.0.vencidas', 1)
            ->assertJsonPath('data.0.pedidos.0.id', $vencido->id)
            ->assertJsonPath('data.0.pedidos.0.vencido', true)
            ->assertJsonPath('data.0.pedidos.1.id', $otro->id)
            ->assertJsonPath('data.1.persona.nombre', 'Diego')
            ->assertJsonPath('data.1.persona.soy_yo', true)
            ->assertJsonPath('data.1.abiertas', 1);
    }

    public function test_mi_gente_incluye_subordinados_indirectos_y_no_a_otra_rama(): void
    {
        ['hernan' => $hernan, 'diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->crear($laura, $carla);

        $nombres = array_column((array) $this->actingAs($hernan)->getJson('/api/pedidos/de-mi-gente')->assertOk()->json('data.*.persona'), 'nombre');
        sort($nombres);
        $this->assertSame(['Carla', 'Diego', 'Hernán'], $nombres);

        $nombres = array_column((array) $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->json('data.*.persona'), 'nombre');
        sort($nombres);
        $this->assertSame(['Carla', 'Diego'], $nombres);
    }

    public function test_direccion_ve_a_todos(): void
    {
        $direccion = $this->persona('Martín', 'Dirección');
        $laura = $this->laura();
        $this->crear($laura, $this->carla());

        $nombres = array_column((array) $this->actingAs($direccion)->getJson('/api/pedidos/de-mi-gente')->assertOk()->json('data.*.persona'), 'nombre');

        $this->assertContains('Carla', $nombres);
        $this->assertContains('Laura', $nombres);
        $this->assertContains('Martín', $nombres);
    }

    public function test_sin_gente_a_cargo_devuelve_403(): void
    {
        $laura = $this->laura();
        $this->persona('Pablo', 'Compras', ['jefe_id' => $laura->id, 'activo' => false]);

        $this->actingAs($laura)->getJson('/api/pedidos/de-mi-gente')->assertForbidden()->assertJsonPath('message', 'Esta sección es para quien tiene gente a cargo.');
        $this->actingAs($laura)->getJson('/api/pedidos/carga-por-persona')->assertForbidden();
    }

    public function test_grupos_ordenados_por_vencidas_y_despues_abiertas(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $pablo = $this->persona('Pablo', 'Compras', ['jefe_id' => $diego->id]);
        $this->persona('Sofía', 'Calidad', ['jefe_id' => $diego->id]);
        $this->crear($laura, $carla);
        $this->crear($laura, $carla);
        $this->crear($laura, $pablo, null, ['fecha_entrega' => '2026-10-16']);

        $nombres = array_column((array) $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->json('data.*.persona'), 'nombre');

        $this->assertSame(['Pablo', 'Carla', 'Diego', 'Sofía'], $nombres);
        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->assertJsonPath('data.3.abiertas', 0)->assertJsonPath('data.3.pedidos', []);
    }

    public function test_inactivo_con_abiertas_aparece_marcado(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->crear($laura, $carla);
        $carla->update(['activo' => false]);
        $this->persona('Pablo', 'Compras', ['jefe_id' => $diego->id, 'activo' => false]);
        $this->persona('Sofía', 'Calidad', ['jefe_id' => $diego->id]);

        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.persona.nombre', 'Carla')
            ->assertJsonPath('data.0.persona.activo', false)
            ->assertJsonPath('data.0.pedidos.0.propietario.activo', false);
    }

    public function test_filtro_estado_de_mi_gente(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $this->crear($laura, $carla);

        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente?estado=completada')
            ->assertJsonPath('data.0.persona.nombre', 'Carla')
            ->assertJsonPath('data.0.abiertas', 1)
            ->assertJsonCount(1, 'data.0.pedidos')
            ->assertJsonPath('data.0.pedidos.0.id', $completado->id);
        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente?estado=perdido')->assertUnprocessable();
    }

    public function test_cambio_de_jefe_se_refleja_al_volver_a_abrir(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->persona('Pablo', 'Compras', ['jefe_id' => $diego->id]);
        $carla->update(['jefe_id' => $laura->id]);

        $nombres = array_column((array) $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->json('data.*.persona'), 'nombre');

        $this->assertNotContains('Carla', $nombres);
    }

    public function test_carga_por_persona_cuenta_abiertas_vencidas_y_esta_semana(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $this->crear($laura, $carla, null, ['fecha_entrega' => self::HOY]);
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-25']);
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-26']);
        $completado = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-20']);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')
            ->assertOk()
            ->assertJsonPath('data.semana', ['desde' => '2026-10-19', 'hasta' => '2026-10-25'])
            ->assertJsonPath('data.personas.0.persona.nombre', 'Carla')
            ->assertJsonPath('data.personas.0.sector.nombre', 'Compras')
            ->assertJsonPath('data.personas.0.abiertas', 4)
            ->assertJsonPath('data.personas.0.vencidas', 1)
            ->assertJsonPath('data.personas.0.vencen_esta_semana', 2)
            ->assertJsonPath('data.personas.1.persona.soy_yo', true);
    }

    public function test_vencen_esta_semana_no_incluye_vencidas(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);

        $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')
            ->assertJsonPath('data.personas.0.vencidas', 1)
            ->assertJsonPath('data.personas.0.vencen_esta_semana', 0);
    }

    public function test_semana_va_de_lunes_a_domingo_en_hora_de_argentina(): void
    {
        ['diego' => $diego] = $this->jerarquia();
        // Domingo 25-oct 23:30 en Argentina = lunes 26-oct 02:30 UTC: sigue siendo la semana del 19 al 25.
        $this->travelTo(Carbon::parse('2026-10-26 02:30:00', 'UTC'));

        $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')->assertJsonPath('data.semana', ['desde' => '2026-10-19', 'hasta' => '2026-10-25']);
    }

    public function test_carga_por_persona_ordena_por_esta_semana_y_despues_vencidas(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $pablo = $this->persona('Pablo', 'Compras', ['jefe_id' => $diego->id]);
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-16']);
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-15']);
        $this->crear($laura, $pablo, null, ['fecha_entrega' => '2026-10-22']);

        $nombres = array_column((array) $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')->json('data.personas.*.persona'), 'nombre');

        $this->assertSame(['Pablo', 'Carla', 'Diego'], $nombres);
    }

    public function test_carga_por_persona_filtra_por_sector(): void
    {
        ['diego' => $diego] = $this->jerarquia();

        $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona?sector_id='.$this->sector('Compras')->id)
            ->assertJsonCount(1, 'data.personas')
            ->assertJsonPath('data.personas.0.persona.nombre', 'Carla');
        $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona?sector_id=abc')->assertUnprocessable();
    }

    public function test_las_vistas_de_jefatura_no_hacen_una_consulta_por_persona(): void
    {
        ['diego' => $diego, 'carla' => $carla, 'laura' => $laura] = $this->jerarquia();
        $this->crear($laura, $carla, $this->obra());
        $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->assertOk();
        $tareas = $this->consultasDe(fn () => $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->assertOk());
        $carga = $this->consultasDe(fn () => $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')->assertOk());

        foreach (['Pablo', 'Marta', 'Juan', 'Rocío'] as $nombre) {
            $persona = $this->persona($nombre, 'Calidad', ['jefe_id' => $diego->id]);
            $this->crear($laura, $persona, $this->obra(nombre: "Obra de {$nombre}"));
        }

        $this->assertLessThanOrEqual($tareas, $this->consultasDe(fn () => $this->actingAs($diego)->getJson('/api/pedidos/de-mi-gente')->assertOk()->assertJsonCount(6, 'data')));
        $this->assertLessThanOrEqual($carga, $this->consultasDe(fn () => $this->actingAs($diego)->getJson('/api/pedidos/carga-por-persona')->assertOk()->assertJsonCount(6, 'data.personas')));
    }
}
