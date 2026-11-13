<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** HU-015 · F-18 (W-21, W-20): presupuestos adicionales y corrección de presupuestos. */
final class PresupuestosTest extends ObrasTestCase
{
    public function test_agrega_adicional_y_la_obra_los_muestra_en_orden_de_alta(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-131-2026 a1', 'descripcion' => 'Reparación de juntas'])
            ->assertCreated()
            ->assertJsonPath('data.numero', '2-131-2026 A1')
            ->assertJsonPath('data.es_principal', false)
            ->assertJsonPath('data.modo_carga', 'sin_cargar')
            ->assertJsonPath('data.oferta', null);
        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-131-2026 A2'])->assertCreated();

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")
            ->assertJsonPath('data.presupuestos.*.numero', ['2-131-2026', '2-131-2026 A1', '2-131-2026 A2'])
            ->assertJsonPath('data.adicionales', 2);
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->assertJsonPath('data.0.que', 'Agregó el presupuesto adicional 2-131-2026 A2');
    }

    public function test_adicional_exige_numero_y_lo_demas_es_opcional(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['descripcion' => 'Sin número'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['numero']);
    }

    public function test_adicional_puede_tener_numero_propio_y_oferta_y_oc_como_archivo_o_link(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $oferta = $this->link($laura, 'presupuesto');
        $oc = $this->subir($laura, 'aprobacion');

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", [
            'numero' => '2-140-2025', 'revision' => 'rev 1', 'oferta' => $oferta,
            'aprobacion' => ['tipo' => 'mail', 'adjunto' => $oc],
        ])->assertCreated()
            ->assertJsonPath('data.numero', '2-140-2025')
            ->assertJsonPath('data.revision', 'REV01')
            ->assertJsonPath('data.oferta.tipo', 'link')
            ->assertJsonPath('data.aprobacion.etiqueta', 'Mail de aprobación')
            ->assertJsonPath('data.aprobacion.adjunto.descarga', "/api/adjuntos/{$oc}");
    }

    public function test_sugiere_siguiente_sufijo_de_adicional(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/presupuestos/sugerir-numero")->assertJsonPath('data.numero', '2-131-2026 A1');
        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-131-2026 A1'])->assertCreated();
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/presupuestos/sugerir-numero")->assertJsonPath('data.numero', '2-131-2026 A2');
    }

    public function test_unicidad_ignora_la_revision(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-131-2026', 'revision' => 'REV02'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['numero' => "El presupuesto 2-131-2026 ya está en la obra 'Piso industrial nave 3'."]);
    }

    public function test_obra_terminada_admite_adicional(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura, ObraEstado::Terminada);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-500-2026'])->assertCreated();
    }

    public function test_agregar_adicional_exige_obras_editar_y_ver_la_obra(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $obra = $this->obraDe($this->diego());

        $this->actingAs($this->carla())->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-501-2026'])->assertForbidden();
        $this->actingAs($this->laura())->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-501-2026'])->assertNotFound();
        $this->assertSame(1, Presupuesto::query()->count());
    }

    public function test_adicional_con_idempotency_key_no_duplica(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $clave = (string) Str::uuid();

        foreach ([1, 2] as $vez) {
            $this->actingAs($laura)->withHeader('Idempotency-Key', $clave)->postJson("/api/obras/{$obra->id}/presupuestos", ['numero' => '2-777-2026'])->assertCreated();
        }

        $this->assertSame(2, Presupuesto::query()->count());
    }

    public function test_revision_nueva_es_el_mismo_presupuesto_y_queda_en_el_historial(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $principal = Presupuesto::query()->sole();

        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['revision' => 'rev 2', 'aprobacion' => ['numero' => '4500-000123']])
            ->assertOk()
            ->assertJsonPath('data.revision', 'REV02')
            ->assertJsonPath('data.aprobacion.tipo', 'oc');

        $this->assertSame(1, Presupuesto::query()->count());
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', 'Revisión: REV01 → REV02')
            ->assertJsonPath('data.1.que', 'Documento de aprobación: (vacío) → Orden de compra')
            ->assertJsonPath('data.2.que', 'N.º de OC: (vacío) → 4500-000123');
    }

    public function test_cambiar_oferta_borra_el_archivo_anterior_y_se_informa_sin_url(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $principal = Presupuesto::query()->sole();
        $anterior = Adjunto::query()->sole();
        $nueva = $this->link($laura, 'presupuesto', 'https://drive.example/secreto/oferta-rev2');

        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['oferta' => $nueva])->assertOk()->assertJsonPath('data.oferta.id', $nueva);

        $this->assertNull(Adjunto::query()->find($anterior->id));
        $this->assertNotNull($anterior->ruta);
        Storage::disk('adjuntos')->assertMissing($anterior->ruta);
        $historial = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->assertOk();
        $this->assertStringNotContainsString('secreto', (string) $historial->getContent());
        $this->assertStringNotContainsString((string) $anterior->ruta, (string) $historial->getContent());
        $historial->assertJsonPath('data.0.que', 'Oferta técnica: quitado (oferta-ejemplo.pdf)');
    }

    public function test_principal_no_queda_sin_oferta_ni_se_convierte_en_adicional(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura);
        $principal = Presupuesto::query()->sole();

        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['oferta' => null])
            ->assertUnprocessable()->assertJsonValidationErrors(['oferta']);
        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['es_principal' => false])
            ->assertUnprocessable()->assertJsonValidationErrors(['es_principal']);
        $this->actingAs($laura)->patchJson("/api/presupuestos/{$principal->id}", ['revision' => null])
            ->assertUnprocessable()->assertJsonValidationErrors(['revision']);
        $this->assertTrue($principal->refresh()->es_principal);
    }

    public function test_adicional_sin_montos_se_puede_quitar(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura, null, ['adicionales' => [['numero' => '2-131-2026 A1']]]);
        $adicional = Presupuesto::query()->where('es_principal', false)->sole();

        $this->actingAs($laura)->deleteJson("/api/presupuestos/{$adicional->id}")->assertOk()->assertJsonPath('data', null);

        $this->assertNull(Presupuesto::query()->find($adicional->id));
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->assertJsonPath('data.0.que', 'Quitó el presupuesto adicional 2-131-2026 A1');
    }

    public function test_principal_o_adicional_con_montos_no_se_quita(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura, null, ['adicionales' => [['numero' => '2-131-2026 A1']]]);
        $principal = Presupuesto::query()->where('es_principal', true)->sole();
        $adicional = Presupuesto::query()->where('es_principal', false)->sole();
        PresupuestoMonto::query()->create(['presupuesto_id' => $adicional->id, 'rubro_id' => null, 'monto' => '1000.00']);

        $this->actingAs($laura)->deleteJson("/api/presupuestos/{$principal->id}")->assertUnprocessable();
        $this->actingAs($laura)->deleteJson("/api/presupuestos/{$adicional->id}")->assertUnprocessable();
        $this->assertSame(2, Presupuesto::query()->count());
    }

    public function test_editar_o_quitar_presupuesto_exige_obras_editar_y_alcance(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $this->crearObra($this->diego());
        $principal = Presupuesto::query()->sole();

        $this->actingAs($this->carla())->patchJson("/api/presupuestos/{$principal->id}", ['descripcion' => 'X'])->assertForbidden();
        $this->actingAs($this->laura())->patchJson("/api/presupuestos/{$principal->id}", ['descripcion' => 'X'])->assertNotFound();
        $this->actingAs($this->laura())->deleteJson("/api/presupuestos/{$principal->id}")->assertNotFound();
        $this->actingAs($this->laura())->patchJson('/api/presupuestos/999999', ['descripcion' => 'X'])->assertNotFound();
    }
}
