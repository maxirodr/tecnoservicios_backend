<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Domain\Models\PreferenciaAviso;

/** F-09 · F-10 · HU-047 (W-19 "Mis avisos", RN-AVI-19 y RN-AVI-27, resuelta DU-P19). */
final class MisAvisosPorMailTest extends AvisosTestCase
{
    public function test_preferencias_sin_fila_usan_el_valor_por_defecto_en_el_orden_de_w19(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->getJson('/api/me/avisos-por-mail')
            ->assertOk()
            ->assertJsonPath('data', [
                ['tipo' => 'asignado', 'nombre' => 'Me asignan un pedido', 'por_mail' => true, 'se_puede_cambiar' => false],
                ['tipo' => 'reasignado', 'nombre' => 'Me reasignan un pedido', 'por_mail' => true, 'se_puede_cambiar' => false],
                ['tipo' => 'tomado', 'nombre' => 'Toman (En curso) un pedido que pedí', 'por_mail' => false, 'se_puede_cambiar' => true],
                ['tipo' => 'completado', 'nombre' => 'Completan un pedido que pedí', 'por_mail' => false, 'se_puede_cambiar' => true],
                ['tipo' => 'vence_manana', 'nombre' => 'Vence mañana una tarea mía', 'por_mail' => false, 'se_puede_cambiar' => false],
                ['tipo' => 'bloqueado', 'nombre' => 'Bloquean un pedido que pedí, con el motivo', 'por_mail' => false, 'se_puede_cambiar' => true],
                ['tipo' => 'comentado', 'nombre' => 'Comentan en un pedido mío', 'por_mail' => false, 'se_puede_cambiar' => true],
                ['tipo' => 'reabierto', 'nombre' => 'Reabren un pedido que completé', 'por_mail' => false, 'se_puede_cambiar' => true],
            ]);
    }

    public function test_preferencia_de_mail_se_guarda_por_tipo_y_se_puede_desactivar(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['tomado' => true, 'completado' => true]])
            ->assertOk()
            ->assertJsonPath('message', 'Guardamos tus avisos por mail.')
            ->assertJsonPath('data.2.por_mail', true)
            ->assertJsonPath('data.3.por_mail', true);

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['tomado' => false]])->assertOk()->assertJsonPath('data.2.por_mail', false);

        $this->assertSame(2, PreferenciaAviso::query()->where('user_id', $laura->id)->count());
    }

    public function test_no_se_puede_destildar_la_asignacion(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['asignado' => false, 'reasignado' => false]])
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'preferencias.asignado' => ['La asignación siempre llega por mail.'],
                'preferencias.reasignado' => ['La asignación siempre llega por mail.'],
            ]);
    }

    public function test_no_se_puede_activar_vence_manana_ni_el_aviso_al_superior_por_mail(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['vence_manana' => true, 'completado_gente' => true]])
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'preferencias.vence_manana' => ['Este aviso llega solo dentro del sistema.'],
                'preferencias.completado_gente' => ['Este aviso llega solo dentro del sistema.'],
            ]);
        $this->assertSame(0, PreferenciaAviso::query()->count());
    }

    public function test_tipo_inexistente_o_valor_no_booleano_da_422(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['otro' => true, 'tomado' => 'tal vez']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preferencias.otro', 'preferencias.tomado']);
        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', [])->assertUnprocessable()->assertJsonValidationErrors(['preferencias']);
    }

    public function test_mandar_el_valor_fijo_no_es_un_error(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['preferencias' => ['asignado' => true, 'vence_manana' => false, 'tomado' => true]])->assertOk();

        $this->assertSame(['tomado'], PreferenciaAviso::query()->pluck('tipo')->map(static fn ($t) => $t->value)->all());
    }

    public function test_preferencias_de_otro_usuario_no_se_leen_ni_se_cambian(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        PreferenciaAviso::query()->create(['user_id' => $carla->id, 'tipo' => 'tomado', 'por_mail' => true]);

        $this->actingAs($laura)->getJson('/api/me/avisos-por-mail?user_id='.$carla->id)->assertJsonPath('data.2.por_mail', false);
        $this->actingAs($laura)->putJson('/api/me/avisos-por-mail', ['user_id' => $carla->id, 'preferencias' => ['tomado' => false]])->assertOk();

        $this->assertTrue(PreferenciaAviso::query()->where('user_id', $carla->id)->valueOrFail('por_mail'));
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson('/api/me/avisos-por-mail')->assertUnauthorized();
        $this->putJson('/api/me/avisos-por-mail', ['preferencias' => ['tomado' => true]])->assertUnauthorized();
    }
}
