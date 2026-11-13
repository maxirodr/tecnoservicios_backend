<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Events\PresupuestoMontosCambiados;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** HU-018 · HU-019 · F-48 · F-54 dato (W-13): presupuesto por rubro y monto de la obra. */
final class PresupuestoPorRubroTest extends ObrasTestCase
{
    public function test_migracion_carga_los_trece_rubros_en_orden(): void
    {
        $this->assertSame(
            ['MATERIALES', 'INSUMOS', 'EPP', 'SUBCONTRATOS', 'LOGISTICA', 'AMORTIZACION', 'MANTENIMIENTO', 'OBRADORES/BAÑOS',
                'SERVICIOS GENERALES', 'MAQUINAS/HERRAMIENTAS', 'VIATICOS', 'FIJOS', 'HORAS LIQUIDADAS'],
            Rubro::query()->orderBy('orden')->pluck('nombre')->all(),
        );
        $this->assertSame(['fijos', 'horas_liquidadas'], Rubro::query()->where('carga_por_periodo', true)->orderBy('orden')->pluck('clave')->all());
    }

    public function test_epp_no_se_presupuesta_y_resume_en_insumos(): void
    {
        $epp = Rubro::query()->where('clave', 'epp')->sole();

        $this->assertFalse($epp->se_presupuesta);
        $this->assertSame(Rubro::query()->where('clave', 'insumos')->value('id'), $epp->resume_en_rubro_id);

        [$diego, $principal] = $this->obraConPrincipal();
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['epp' => '100.00', 'insumo' => '5.00']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['montos.epp', 'montos.insumo']);
    }

    public function test_w13_muestra_los_12_rubros_y_una_columna_por_presupuesto_sin_cargar(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();

        $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/presupuesto-por-rubro")
            ->assertOk()
            ->assertJsonCount(12, 'data.rubros')
            ->assertJsonPath('data.rubros.0', ['clave' => 'materiales', 'nombre' => 'MATERIALES'])
            ->assertJsonPath('data.presupuestos.0.modo_carga', 'sin_cargar')
            ->assertJsonPath('data.presupuestos.0.total', null)
            ->assertJsonPath('data.total_obra.total', '0.00');
    }

    public function test_presupuesto_se_carga_por_rubro_y_el_total_de_la_obra_suma_todos(): void
    {
        Event::fake([PresupuestoMontosCambiados::class]);
        [$diego, $principal] = $this->obraConPrincipal();
        $adicional = Presupuesto::query()->create(['obra_id' => $principal->obra_id, 'numero' => '2-131-2026 A1', 'created_by' => $diego->id]);

        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['materiales' => '10900000.00', 'insumos' => '1500000.50', 'subcontratos' => null]])
            ->assertOk()
            ->assertJsonPath('data.presupuestos.0.montos.materiales', '10900000.00')
            ->assertJsonPath('data.presupuestos.0.montos.subcontratos', null)
            ->assertJsonPath('data.presupuestos.0.total', '12400000.50');
        $this->montos($diego, $adicional, ['modo' => 'solo_total', 'total' => 3250000])
            ->assertOk()
            ->assertJsonPath('data.presupuestos.1.modo_carga', 'solo_total')
            ->assertJsonPath('data.presupuestos.1.montos.materiales', null)
            ->assertJsonPath('data.presupuestos.1.total', '3250000.00')
            ->assertJsonPath('data.total_obra.por_rubro.materiales', '10900000.00')
            ->assertJsonPath('data.total_obra.total', '15650000.50');

        Event::assertDispatched(PresupuestoMontosCambiados::class, fn (PresupuestoMontosCambiados $e): bool => $e->obraId === $principal->obra_id);
    }

    public function test_pasar_a_solo_total_reemplaza_los_rubros_y_queda_en_el_historial(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['materiales' => '10900000.00', 'logistica' => '200.00']])->assertOk();

        $this->montos($diego, $principal, ['modo' => 'solo_total', 'total' => '25650000.00'])
            ->assertOk()
            ->assertJsonPath('data.presupuestos.0.montos.materiales', null)
            ->assertJsonPath('data.presupuestos.0.total', '25650000.00');

        $this->assertSame(1, $principal->montos()->count());
        $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/historial")
            ->assertJsonPath('data.0.que', 'MATERIALES: 10.900.000 → (vacío)')
            ->assertJsonPath('data.3.que', 'Presupuesto por rubro: por rubro → solo el total');
    }

    public function test_monto_negativo_o_con_mas_de_dos_decimales_es_rechazado(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();

        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['logistica' => '-150000']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['montos.logistica' => 'El monto no puede ser negativo.']);
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['logistica' => '1.005', 'insumos' => '1.500.000,50']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['montos.logistica' => 'El monto lleva como máximo 2 decimales.', 'montos.insumos']);
        $this->montos($diego, $principal, ['modo' => 'solo_total', 'montos' => ['materiales' => '1.00'], 'total' => '1.00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['montos']);
    }

    public function test_solo_presupuesto_rubro_editar_carga_montos(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $principal = Presupuesto::query()->sole();

        // Ingeniería: Presupuesto por rubro = Ver (solo sus obras): lo ve en su obra, no lo carga.
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/presupuesto-por-rubro")->assertOk();
        $this->montos($laura, $principal, ['modo' => 'solo_total', 'total' => '1.00'])->assertForbidden();
        $this->montos($this->carla(), $principal, ['modo' => 'solo_total', 'total' => '1.00'])->assertOk();
        $this->montos($this->usuarioDe('Calidad'), $principal, ['modo' => 'solo_total', 'total' => '1.00'])->assertForbidden();
    }

    public function test_presupuesto_rubro_ver_solo_sus_obras_no_ve_la_de_otro(): void
    {
        $laura = $this->laura();
        $ajena = $this->obraDe($this->diego());

        $this->actingAs($laura)->getJson("/api/obras/{$ajena->id}/presupuesto-por-rubro")->assertForbidden();
        $this->actingAs($this->usuarioDe('Calidad'))->getJson("/api/obras/{$ajena->id}/presupuesto-por-rubro")->assertForbidden();
        $this->actingAs($laura)->getJson('/api/obras/999999/presupuesto-por-rubro')->assertNotFound();
    }

    public function test_obra_sin_presupuesto_cargado_se_usa_igual(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->assertOk()->assertJsonPath('data.presupuestos.0.modo_carga', 'sin_cargar');
        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'en_ejecucion'])->assertOk();
    }

    public function test_monto_obra_solo_lo_carga_monto_obra_editar(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();

        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '36500000.00'])
            ->assertOk()
            ->assertJsonPath('data.monto_obra', '36500000.00');
        $this->actingAs($this->carla())->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '1.00'])->assertForbidden();
        $this->actingAs($this->usuarioDe('Dirección'))->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '1.00'])->assertForbidden();
        $this->assertSame('36500000.00', $principal->refresh()->monto_obra);
    }

    public function test_monto_obra_se_omite_del_json_sin_permiso(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '36500000.00'])->assertOk();

        $this->actingAs($this->usuarioDe('Dirección'))->getJson("/api/obras/{$principal->obra_id}/presupuesto-por-rubro")
            ->assertJsonPath('data.presupuestos.0.monto_obra', '36500000.00')
            ->assertJsonPath('data.total_obra.monto_obra', '36500000.00');
        $carla = $this->actingAs($this->carla())->getJson("/api/obras/{$principal->obra_id}/presupuesto-por-rubro")->assertOk();
        $this->assertArrayNotHasKey('monto_obra', (array) $carla->json('data.presupuestos.0'));
        $this->assertArrayNotHasKey('monto_obra', (array) $carla->json('data.total_obra'));
        $this->assertStringNotContainsString('36500000', (string) $carla->getContent());
    }

    public function test_historial_oculta_montos_y_monto_obra_sin_permiso(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->montos($diego, $principal, ['modo' => 'solo_total', 'total' => '25650000.00'])->assertOk();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '36500000.00'])->assertOk();
        $obraId = $principal->obra_id;

        $this->actingAs($diego)->getJson("/api/obras/{$obraId}/historial")->assertJsonPath('data.0.que', 'Monto del presupuesto: (vacío) → 36.500.000');

        $calidad = (string) $this->actingAs($this->usuarioDe('Calidad'))->getJson("/api/obras/{$obraId}/historial")->assertOk()->getContent();
        foreach (['25650000', '25.650.000', '36500000', '36.500.000'] as $monto) {
            $this->assertStringNotContainsString($monto, $calidad);
        }

        $carla = (string) $this->actingAs($this->carla())->getJson("/api/obras/{$obraId}/historial")->assertOk()->getContent();
        $this->assertStringContainsString('25.650.000', $carla);
        $this->assertStringNotContainsString('36500000', $carla);
        $this->assertStringNotContainsString('36.500.000', $carla);
    }

    public function test_correccion_de_montos_guarda_el_valor_anterior(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['viaticos' => '1000.00']])->assertOk();
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['viaticos' => '1200.00']])->assertOk();

        $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/historial")->assertJsonPath('data.0.que', 'VIATICOS: 1.000 → 1.200');
    }

    public function test_historial_muestra_los_importes_como_en_el_presupuesto_por_rubro(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->montos($diego, $principal, ['modo' => 'por_rubro', 'montos' => ['materiales' => '1000.00', 'logistica' => '12400000.50']])->assertOk();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '2500.00'])->assertOk();

        $lineas = array_column((array) $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/historial")->assertOk()->json('data'), 'que');

        $this->assertContains('Monto del presupuesto: (vacío) → 2.500', $lineas);
        $this->assertContains('MATERIALES: (vacío) → 1.000', $lineas);
        $this->assertContains('LOGISTICA: (vacío) → 12.400.000,5', $lineas);
        $this->assertContains('Presupuesto por rubro: sin cargar → por rubro', $lineas);
        $this->assertSame([], preg_grep('/\d\.\d{2}(?!\d)/', $lineas), 'Ningún importe con punto decimal crudo.');
    }

    /**
     * @return array{0: User, 1: Presupuesto}
     */
    private function obraConPrincipal(): array
    {
        $diego = $this->diego();
        $obra = Obra::factory()->deResponsable($diego)->create();

        return [$diego, Presupuesto::query()->where('obra_id', $obra->id)->sole()];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return TestResponse<JsonResponse>
     */
    private function montos(User $user, Presupuesto $presupuesto, array $payload): TestResponse
    {
        return $this->actingAs($user)->putJson("/api/presupuestos/{$presupuesto->id}/montos", $payload);
    }
}
