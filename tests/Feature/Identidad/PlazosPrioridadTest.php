<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Identidad\Domain\Models\Plazos;
use Carbon\CarbonImmutable;

/** F-68 · W-50 · HU-011 (RN-IDE-40…44). */
final class PlazosPrioridadTest extends IdentidadTestCase
{
    public function test_migracion_arranca_plazos_en_1_y_7(): void
    {
        $this->actingAs($this->usuarioDe('Dirección'))->getJson('/api/plazos-prioridad')
            ->assertOk()
            ->assertJsonPath('data.urgente_hasta_dias', 1)
            ->assertJsonPath('data.prioritario_hasta_dias', 7)
            ->assertJsonPath('data.updated_by', null);
    }

    public function test_ver_plazos_exige_plazos_prioridad_ver(): void
    {
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/plazos-prioridad')->assertForbidden();
    }

    public function test_solo_plazos_editar_cambia_los_plazos(): void
    {
        $this->actingAs($this->usuarioDe('Dirección'))->putJson('/api/plazos-prioridad', $this->plazos(2, 5))->assertForbidden();
        $this->actingAs($this->usuarioDe('Jefatura de Administración'))->putJson('/api/plazos-prioridad', $this->plazos(2, 5))->assertForbidden();

        $gerencia = $this->usuarioDe('Gerencia de Operaciones', ['nombre' => 'Hernán']);
        $this->actingAs($gerencia)->putJson('/api/plazos-prioridad', $this->plazos(2, 5))
            ->assertOk()
            ->assertJsonPath('data.urgente_hasta_dias', 2)
            ->assertJsonPath('data.prioritario_hasta_dias', 5)
            ->assertJsonPath('data.updated_by', ['id' => $gerencia->id, 'nombre' => 'Hernán']);
    }

    public function test_prioritario_debe_ser_mayor_que_urgente(): void
    {
        $this->actingAs($this->administrador())->putJson('/api/plazos-prioridad', $this->plazos(3, 2))
            ->assertUnprocessable()
            ->assertJsonPath('errors.prioritario_hasta_dias.0', 'Prioritario tiene que ser mayor que Urgente.');
        $this->actingAs($this->administrador())->putJson('/api/plazos-prioridad', $this->plazos(3, 3))
            ->assertUnprocessable();

        $this->assertDatabaseHas('plazos_prioridad', ['urgente_hasta_dias' => 1, 'prioritario_hasta_dias' => 7]);
    }

    public function test_plazos_no_aceptan_negativos_ni_vacios(): void
    {
        $this->actingAs($this->administrador())->putJson('/api/plazos-prioridad', ['urgente_hasta_dias' => -1, 'prioritario_hasta_dias' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['urgente_hasta_dias', 'prioritario_hasta_dias']);
    }

    public function test_cambio_de_plazos_queda_registrado_con_los_valores_anteriores(): void
    {
        $admin = $this->administrador();
        $this->actingAs($admin)->putJson('/api/plazos-prioridad', $this->plazos(0, 5))->assertOk();

        $registros = $this->actividades(Plazos::class, 'editado');
        $this->assertCount(1, $registros);
        $this->assertSame($admin->id, $registros[0]->user_id);
        $this->assertSame([
            'urgente_hasta_dias' => ['antes' => 1, 'despues' => 0],
            'prioritario_hasta_dias' => ['antes' => 7, 'despues' => 5],
        ], $registros[0]->cambios);
    }

    public function test_calculo_usa_los_plazos_vigentes(): void
    {
        // Caso borde 13: urgente 0, prioritario 5; pedido del lunes.
        $this->actingAs($this->administrador())->putJson('/api/plazos-prioridad', $this->plazos(0, 5))->assertOk();
        $plazos = $this->app->make(PlazosPrioridad::class);
        $lunes = CarbonImmutable::parse('2026-11-16');

        $this->assertSame(Prioridad::Urgente, $plazos->calcular($lunes, $lunes));
        $this->assertSame(Prioridad::Prioritario, $plazos->calcular($lunes, $lunes->addDays(5)));
        $this->assertSame(Prioridad::Normal, $plazos->calcular($lunes, $lunes->addDays(6)));
    }

    /**
     * @return array{urgente_hasta_dias: int, prioritario_hasta_dias: int}
     */
    private function plazos(int $urgente, int $prioritario): array
    {
        return ['urgente_hasta_dias' => $urgente, 'prioritario_hasta_dias' => $prioritario];
    }
}
