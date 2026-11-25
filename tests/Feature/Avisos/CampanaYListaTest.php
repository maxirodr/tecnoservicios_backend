<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Domain\Models\Aviso;

/** F-08 · F-64 · HU-045 · HU-049 (RN-AVI-22…26, avisos.md §7 y §9): campana, lista, leído; solo los míos. */
final class CampanaYListaTest extends AvisosTestCase
{
    public function test_contador_cuenta_solo_mis_no_leidos(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $this->aviso($laura);
        $this->aviso($laura);
        $this->aviso($laura, ['leido_at' => now()]);
        $this->aviso($carla);

        $this->actingAs($laura)->getJson('/api/avisos/no-leidos/contador')
            ->assertOk()
            ->assertExactJson(['data' => ['no_leidos' => 2], 'message' => null, 'errors' => null]);
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson('/api/avisos/no-leidos/contador')->assertUnauthorized();
        $this->getJson('/api/avisos')->assertUnauthorized();
        $this->postJson('/api/avisos/leer-todos')->assertUnauthorized();
        $this->postJson('/api/avisos/1/leido')->assertUnauthorized();
    }

    public function test_lista_ordena_la_mas_nueva_arriba_con_el_formato_de_la_api(): void
    {
        $laura = $this->persona('Laura');
        $this->travelTo(now()->subHour());
        $viejo = $this->aviso($laura);
        $this->travelBack();
        $nuevo = $this->aviso($laura, ['tipo' => 'completado', 'actor_id' => $laura->id, 'datos' => [
            'texto' => 'Carla completó tu tarea Alquiler de obrador', 'tarea' => 'Alquiler de obrador', 'obra' => null, 'actor_nombre' => 'Carla',
        ]]);

        $respuesta = $this->actingAs($laura)->getJson('/api/avisos')->assertOk();

        $respuesta->assertJsonPath('data.0.id', $nuevo->id)
            ->assertJsonPath('data.1.id', $viejo->id)
            ->assertJsonPath('data.0.texto', 'Carla completó tu tarea Alquiler de obrador')
            ->assertJsonPath('data.0.pedido', ['id' => 88, 'tarea' => 'Alquiler de obrador'])
            ->assertJsonPath('data.0.obra', null)
            ->assertJsonPath('data.0.actor', ['id' => $laura->id, 'nombre' => 'Carla'])
            ->assertJsonPath('data.0.leido', false)
            ->assertJsonPath('data.1.obra', ['id' => 12, 'nombre_trabajo' => 'Piso industrial nave 3'])
            ->assertJsonPath('data.1.actor', null)
            ->assertJsonPath('meta.no_leidos', 2)
            ->assertJsonPath('meta.per_page', 25);
        $this->assertMatchesRegularExpression('/-03:00$/', (string) $respuesta->json('data.0.created_at'));
    }

    public function test_lista_de_avisos_filtra_leidos_no_leidos_y_tipo(): void
    {
        $laura = $this->persona('Laura');
        $noLeido = $this->aviso($laura, ['tipo' => 'tomado']);
        $leido = $this->aviso($laura, ['leido_at' => now()]);

        $this->actingAs($laura)->getJson('/api/avisos?leidos=no')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $noLeido->id);
        $this->actingAs($laura)->getJson('/api/avisos?leidos=si')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $leido->id);
        $this->actingAs($laura)->getJson('/api/avisos?tipo=tomado')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $noLeido->id);
        $this->actingAs($laura)->getJson('/api/avisos?leidos=todos&por_pagina=5')->assertJsonCount(2, 'data')->assertJsonPath('meta.per_page', 5);
    }

    public function test_filtros_invalidos_dan_422(): void
    {
        $laura = $this->persona('Laura');

        $this->actingAs($laura)->getJson('/api/avisos?leidos=quizas&tipo=otro&por_pagina=7')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leidos', 'tipo', 'por_pagina']);
    }

    public function test_usuario_solo_lista_sus_avisos(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $this->aviso($carla);

        $this->actingAs($laura)->getJson('/api/avisos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_tocar_un_aviso_lo_marca_leido_y_baja_el_contador(): void
    {
        $laura = $this->persona('Laura');
        $aviso = $this->aviso($laura);

        $this->actingAs($laura)->postJson("/api/avisos/{$aviso->id}/leido")->assertOk()->assertJsonPath('data.leido', true);
        $this->actingAs($laura)->getJson('/api/avisos/no-leidos/contador')->assertJsonPath('data.no_leidos', 0);
    }

    public function test_marcar_leido_es_idempotente(): void
    {
        $laura = $this->persona('Laura');
        $aviso = $this->aviso($laura, ['leido_at' => '2026-11-18 10:00:00']);

        $this->actingAs($laura)->postJson("/api/avisos/{$aviso->id}/leido")->assertOk()->assertJsonPath('data.leido', true);

        $this->assertSame('2026-11-18 10:00:00', Aviso::query()->findOrFail($aviso->id)->leido_at?->format('Y-m-d H:i:s'));
    }

    public function test_aviso_ajeno_devuelve_404(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $deLaura = $this->aviso($laura);

        $this->actingAs($carla)->postJson("/api/avisos/{$deLaura->id}/leido")->assertNotFound()->assertJsonPath('data', null);
        $this->assertNull(Aviso::query()->findOrFail($deLaura->id)->leido_at);
    }

    public function test_marcar_todos_como_leidos_deja_el_contador_en_cero_y_no_toca_los_ajenos(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $this->aviso($laura);
        $this->aviso($laura);
        $this->aviso($laura, ['leido_at' => now()]);
        $deCarla = $this->aviso($carla);

        $this->actingAs($laura)->postJson('/api/avisos/leer-todos')->assertOk()->assertJsonPath('data', ['marcados' => 2]);
        $this->actingAs($laura)->getJson('/api/avisos/no-leidos/contador')->assertJsonPath('data.no_leidos', 0);
        $this->assertNull(Aviso::query()->findOrFail($deCarla->id)->leido_at);
    }

    public function test_usuario_desactivado_no_ve_sus_avisos(): void
    {
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['activo' => false]);

        $this->actingAs($laura)->getJson('/api/avisos/no-leidos/contador')->assertUnauthorized();
    }
}
