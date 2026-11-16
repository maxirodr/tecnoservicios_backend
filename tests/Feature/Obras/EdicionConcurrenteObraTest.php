<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Actividad\Actividad;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** PU-002: dos personas (o dos pestañas) editando la misma obra; la segunda no pisa a la primera. */
final class EdicionConcurrenteObraTest extends ObrasTestCase
{
    private const CONFLICTO = 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.';

    public function test_la_ficha_trae_updated_at_en_iso_8601(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")
            ->assertOk()
            ->assertJsonPath('data.updated_at', Obra::query()->findOrFail($obra->id)->updated_at?->toIso8601String());
    }

    public function test_guardar_con_una_version_vieja_da_409_y_no_cambia_nada(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $abiertaPorLaura = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');

        $this->travel(5)->seconds();
        $primerGuardado = $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'contactos' => ['solicitante' => ['telefono' => '291 555-0001']],
            'updated_at' => $abiertaPorLaura,
        ])->assertOk();
        $this->assertNotSame($abiertaPorLaura, $primerGuardado->json('data.updated_at'), 'Cambiar un contacto cambia la versión de la obra');

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'planta' => 'Muelle 4',
            'contactos' => ['solicitante' => ['telefono' => '291 000-0000']],
            'updated_at' => $abiertaPorLaura,
        ])->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")
            ->assertJsonPath('data.contactos.solicitante.telefono', '291 555-0001')
            ->assertJsonPath('data.planta', $obra->planta);
        $this->assertSame(1, Actividad::query()->where('accion', 'editado')->count());
    }

    public function test_con_la_version_actual_guarda_y_sin_version_guarda_como_siempre(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $version = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');

        $this->travel(2)->seconds();
        $nueva = $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['planta' => 'Muelle 4', 'updated_at' => $version])
            ->assertOk()
            ->assertJsonPath('data.planta', 'Muelle 4')
            ->json('data.updated_at');
        $this->assertNotSame($version, $nueva);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['planta' => 'Muelle 5'])->assertOk();
    }
}
