<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

/** PU-002: editar un proveedor que otra persona cambió mientras estaba abierto. */
final class EdicionConcurrenteProveedorTest extends ProveedoresTestCase
{
    private const CONFLICTO = 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.';

    public function test_version_vieja_da_409_y_version_actual_guarda(): void
    {
        $carla = $this->quienEdita();
        $proveedor = $this->proveedor('Corralón Centro');
        $abierto = $this->actingAs($carla)->getJson("/api/proveedores/{$proveedor->id}")->assertOk()->json('data.updated_at');
        $this->assertSame($proveedor->fresh()?->updated_at?->toIso8601String(), $abierto);

        $this->travel(3)->seconds();
        $actual = $this->actingAs($carla)->patchJson("/api/proveedores/{$proveedor->id}", ['telefono' => '291 555-0001', 'updated_at' => $abierto])
            ->assertOk()
            ->json('data.updated_at');

        $this->actingAs($carla)->patchJson("/api/proveedores/{$proveedor->id}", ['telefono' => '291 000-0000', 'updated_at' => $abierto])
            ->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);
        $this->assertSame('291 555-0001', $proveedor->fresh()?->telefono);

        $this->actingAs($carla)->patchJson("/api/proveedores/{$proveedor->id}", ['telefono' => '291 000-0000', 'updated_at' => $actual])->assertOk();
        $this->actingAs($carla)->patchJson("/api/proveedores/{$proveedor->id}", ['telefono' => '291 000-0002'])->assertOk();
    }
}
