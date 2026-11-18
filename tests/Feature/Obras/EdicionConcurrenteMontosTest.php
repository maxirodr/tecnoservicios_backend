<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Montos\CargarMontoObraAction;
use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\Actividad;
use Tests\Feature\Obras\Support\ObrasTestCase;

/**
 * PU-201: el presupuesto por rubro abierto en dos pestañas (o por dos personas). La segunda no pisa
 * los montos que guardó la primera: con la versión vieja del presupuesto, 409 como Editar obra (PU-002).
 */
final class EdicionConcurrenteMontosTest extends ObrasTestCase
{
    private const CONFLICTO = 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.';

    public function test_el_presupuesto_por_rubro_trae_la_version_de_cada_presupuesto(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $adicional = Presupuesto::query()->create(['obra_id' => $principal->obra_id, 'numero' => '2-131-2026 A1', 'created_by' => $diego->id]);

        $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/presupuesto-por-rubro")
            ->assertOk()
            ->assertJsonPath('data.presupuestos.0.updated_at', $principal->refresh()->updated_at->toIso8601String())
            ->assertJsonPath('data.presupuestos.1.updated_at', $adicional->refresh()->updated_at->toIso8601String());
    }

    public function test_guardar_montos_con_una_version_vieja_da_409_y_no_pisa_nada(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $pestanaB = $this->version($diego, $principal);

        $this->travel(5)->seconds();
        $pestanaA = $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro',
            'montos' => ['materiales' => '2000000.00', 'insumos' => '250000.00'],
            'updated_at' => $pestanaB,
        ])->assertOk();
        $this->assertNotSame($pestanaB, $pestanaA->json('data.presupuestos.0.updated_at'), 'Guardar montos cambia la versión del presupuesto');

        $this->travel(5)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro',
            'montos' => ['subcontratos' => '777000.00'],
            'updated_at' => $pestanaB,
        ])->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);

        $this->actingAs($diego)->getJson("/api/obras/{$principal->obra_id}/presupuesto-por-rubro")
            ->assertJsonPath('data.presupuestos.0.montos.materiales', '2000000.00')
            ->assertJsonPath('data.presupuestos.0.montos.insumos', '250000.00')
            ->assertJsonPath('data.presupuestos.0.montos.subcontratos', null);
        $this->assertSame(1, Actividad::query()->where('accion', CargarMontosAction::ACCION)->count());
    }

    public function test_dos_guardados_en_serie_con_la_version_vieja_no_pisan_y_con_la_nueva_si_guarda(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $abierta = $this->version($diego, $principal);

        $this->travel(3)->seconds();
        $nueva = $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro', 'montos' => ['materiales' => '1000.00'], 'updated_at' => $abierta,
        ])->assertOk()->json('data.presupuestos.0.updated_at');

        foreach ([['modo' => 'solo_total', 'total' => '99.00'], ['modo' => 'por_rubro', 'montos' => ['logistica' => '5.00']]] as $payload) {
            $this->travel(3)->seconds();
            $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [...$payload, 'updated_at' => $abierta])
                ->assertStatus(409);
        }

        $this->assertSame(['1000.00'], $principal->montos()->pluck('monto')->all());
        $this->assertSame('por_rubro', $principal->refresh()->modo_carga->value);

        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro', 'montos' => ['materiales' => '1000.00', 'logistica' => '5.00'], 'updated_at' => $nueva,
        ])->assertOk()->assertJsonPath('data.presupuestos.0.montos.logistica', '5.00');
    }

    public function test_guardar_montos_sube_la_version_del_presupuesto_y_de_la_obra(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $obraAntes = $this->versionObra($diego, $principal->obra_id);
        $presupuestoAntes = $this->version($diego, $principal);

        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", ['modo' => 'por_rubro', 'montos' => ['viaticos' => '10.00']])->assertOk();
        $obraDespues = $this->versionObra($diego, $principal->obra_id);
        $presupuestoDespues = $this->version($diego, $principal);
        $this->assertNotSame($obraAntes, $obraDespues);
        $this->assertNotSame($presupuestoAntes, $presupuestoDespues);

        // Editar obra abierta antes de los montos: 409 (PU-101).
        $this->actingAs($diego)->patchJson("/api/obras/{$principal->obra_id}", ['planta' => 'Planta B', 'updated_at' => $obraAntes])->assertStatus(409);

        // Guardar lo mismo no es un cambio: la versión no se mueve.
        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro', 'montos' => ['viaticos' => '10.00'], 'updated_at' => $presupuestoDespues,
        ])->assertOk()->assertJsonPath('data.presupuestos.0.updated_at', $presupuestoDespues);
        $this->assertSame($obraDespues, $this->versionObra($diego, $principal->obra_id));
    }

    public function test_sin_version_guarda_como_siempre(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $this->version($diego, $principal);

        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", ['modo' => 'solo_total', 'total' => '1.00'])->assertOk();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '2.00'])->assertOk();
    }

    public function test_monto_obra_con_una_version_vieja_da_409_y_no_pisa(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $abierta = $this->version($diego, $principal);

        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '36500000.00', 'updated_at' => $abierta])->assertOk();

        $this->travel(3)->seconds();
        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '1.00', 'updated_at' => $abierta])
            ->assertStatus(409)
            ->assertExactJson(['data' => null, 'message' => self::CONFLICTO, 'errors' => ['updated_at' => [self::CONFLICTO]]]);

        $this->assertSame('36500000.00', $principal->refresh()->monto_obra);
        $this->assertSame(1, Actividad::query()->where('accion', CargarMontoObraAction::ACCION)->count());
    }

    public function test_montos_y_monto_obra_en_serie_con_la_version_que_devuelve_cada_guardado(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();
        $abierta = $this->version($diego, $principal);

        $this->travel(3)->seconds();
        $trasMontos = $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'por_rubro', 'montos' => ['materiales' => '1000.00'], 'updated_at' => $abierta,
        ])->assertOk()->json('data.presupuestos.0.updated_at');

        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/monto-obra", ['monto_obra' => '2500.00', 'updated_at' => $trasMontos])
            ->assertOk()
            ->assertJsonPath('data.monto_obra', '2500.00');
    }

    public function test_la_version_mal_escrita_es_422(): void
    {
        [$diego, $principal] = $this->obraConPrincipal();

        $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", ['modo' => 'solo_total', 'total' => '1.00', 'updated_at' => 'ayer'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['updated_at']);
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

    private function version(User $user, Presupuesto $presupuesto): string
    {
        $columnas = (array) $this->actingAs($user)->getJson("/api/obras/{$presupuesto->obra_id}/presupuesto-por-rubro")->assertOk()->json('data.presupuestos');
        $columna = collect($columnas)->firstWhere('id', $presupuesto->id);
        $this->assertIsArray($columna);
        $this->assertIsString($columna['updated_at']);

        return $columna['updated_at'];
    }

    private function versionObra(User $user, int $obraId): string
    {
        $version = $this->actingAs($user)->getJson("/api/obras/{$obraId}")->assertOk()->json('data.updated_at');
        $this->assertIsString($version);

        return $version;
    }
}
