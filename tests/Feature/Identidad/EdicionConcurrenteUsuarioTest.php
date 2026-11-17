<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

/** PU-002: editar un usuario que otra persona cambió mientras estaba abierto. */
final class EdicionConcurrenteUsuarioTest extends IdentidadTestCase
{
    private const CONFLICTO = 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.';

    public function test_version_vieja_da_409_y_version_actual_guarda(): void
    {
        $admin = $this->administrador();
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $abierto = $this->actingAs($admin)->getJson("/api/usuarios/{$carla->id}")->assertOk()->json('data.updated_at');
        $this->assertSame($carla->fresh()?->updated_at?->toIso8601String(), $abierto);

        $this->travel(3)->seconds();
        $actual = $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['telefono' => '291 555-0001', 'updated_at' => $abierto])
            ->assertOk()
            ->json('data.updated_at');

        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['nombre' => 'Carla B', 'updated_at' => $abierto])
            ->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);
        $this->assertSame('Carla', $carla->fresh()?->nombre);

        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['nombre' => 'Carla B', 'updated_at' => $actual])->assertOk();
        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['nombre' => 'Carla C'])->assertOk();
    }

    public function test_mi_perfil_no_pisa_lo_que_gerencia_cambio_mientras_estaba_abierto(): void
    {
        $admin = $this->administrador();
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla']);
        $abierto = $this->actingAs($carla)->getJson('/api/me')->assertOk()->json('data.updated_at');
        $this->assertSame($carla->fresh()?->updated_at?->toIso8601String(), $abierto);

        $this->travel(3)->seconds();
        $this->actingAs($admin)->patchJson("/api/usuarios/{$carla->id}", ['telefono' => '291 555-0001'])->assertOk();

        $this->actingAs($carla)->patchJson('/api/me', ['nombre' => 'Carla B', 'telefono' => null, 'updated_at' => $abierto])
            ->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);
        $this->assertDatabaseHas('users', ['id' => $carla->id, 'nombre' => 'Carla', 'telefono' => '291 555-0001']);

        // Cada pedido real carga a la persona de nuevo; `actingAs` reusa la instancia del test.
        $carla->refresh();
        $actual = $this->actingAs($carla)->getJson('/api/me')->json('data.updated_at');
        $this->actingAs($carla)->patchJson('/api/me', ['nombre' => 'Carla B', 'updated_at' => $actual])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Carla B');
        $this->actingAs($carla)->patchJson('/api/me', ['nombre' => 'Carla C'])->assertOk();
    }
}
