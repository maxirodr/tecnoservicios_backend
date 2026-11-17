<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Application\Presupuestos\ActualizarPresupuestoAction;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
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
            ->assertJsonPath('data.updated_at', Obra::query()->findOrFail($obra->id)->updated_at->toIso8601String());
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

    /** PU-102: con conflicto no se guarda nada, ni la obra ni su presupuesto principal. */
    public function test_conflicto_con_cambios_en_el_principal_no_guarda_nada(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $abierta = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');

        $this->travel(5)->seconds();
        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['cliente' => 'Otro cliente SA', 'updated_at' => $abierta])->assertOk();

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'planta' => 'Planta B',
            'presupuesto_principal' => ['revision' => 'REV45', 'descripcion' => 'Cambio que no tiene que quedar'],
            'updated_at' => $abierta,
        ])->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);

        $principal = Presupuesto::query()->where('obra_id', $obra->id)->sole();
        $this->assertSame('REV01', $principal->revision);
        $this->assertNull($principal->descripcion);
        $this->assertSame($obra->planta, $obra->fresh()?->planta);
        $this->assertSame(0, Actividad::query()->where('accion', ActualizarPresupuestoAction::ACCION)->count());
    }

    /** PU-101: obra y principal se guardan juntos; el historial del presupuesto se registra como siempre. */
    public function test_guarda_obra_y_principal_en_una_sola_transaccion(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $version = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');

        $this->travel(2)->seconds();
        $nueva = $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'planta' => 'Planta B',
            'presupuesto_principal' => ['revision' => 'rev 2', 'aprobacion' => ['numero' => '4500-000123']],
            'updated_at' => $version,
        ])->assertOk()
            ->assertJsonPath('data.planta', 'Planta B')
            ->assertJsonPath('data.presupuestos.0.revision', 'REV02')
            ->assertJsonPath('data.presupuestos.0.aprobacion.numero', '4500-000123')
            ->json('data.updated_at');
        $this->assertNotSame($version, $nueva);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonFragment(['que' => 'Revisión: REV01 → REV02'])
            ->assertJsonFragment(['que' => 'N.º de OC: (vacío) → 4500-000123']);

        // Un error de la obra que aparece después de guardar el principal lo deshace (una sola transacción).
        $inactivo = $this->usuarioDe('Ingeniería de proyectos', ['activo' => false]);
        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", [
            'responsable_id' => $inactivo->id,
            'presupuesto_principal' => ['revision' => 'REV03'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['responsable_id']);
        $this->assertSame('REV02', Presupuesto::query()->where('obra_id', $obra->id)->sole()->revision);
    }

    public function test_errores_del_principal_vienen_con_la_clave_presupuesto_principal(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['presupuesto_principal' => ['revision' => 'rev dos']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto_principal.revision' => 'Escribí la revisión como REV01.']);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['presupuesto_principal' => ['revision' => null]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto_principal.revision' => 'La revisión es obligatoria en el presupuesto principal.']);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['presupuesto_principal' => ['es_principal' => false]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto_principal.es_principal']);
        $this->assertSame('REV01', Presupuesto::query()->where('obra_id', $obra->id)->sole()->revision);
    }

    /** PU-101: cambiar un presupuesto desde la ficha cambia la versión de la obra. */
    public function test_tocar_un_presupuesto_sube_la_version_de_la_obra(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $principal = Presupuesto::query()->where('obra_id', $obra->id)->sole();
        $abierta = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');

        $this->travel(3)->seconds();
        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['revision' => 'REV25'])->assertOk();
        $tras = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');
        $this->assertNotSame($abierta, $tras);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['presupuesto_principal' => ['revision' => 'REV35'], 'updated_at' => $abierta])
            ->assertStatus(409);
        $this->assertSame('REV25', $principal->fresh()?->revision);

        // Guardar sin cambios no cambia la versión; agregar y quitar un adicional, sí.
        $this->travel(3)->seconds();
        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['revision' => 'REV25'])->assertOk();
        $this->assertSame($tras, $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at'));

        $adicional = $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-131-2026 A1'])->assertCreated()->json('data.id');
        $trasAlta = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at');
        $this->assertNotSame($tras, $trasAlta);

        $this->travel(3)->seconds();
        $this->actingAs($laura)->deleteJson("/api/presupuestos/{$adicional}")->assertSuccessful();
        $this->assertNotSame($trasAlta, $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->json('data.updated_at'));
    }
}
