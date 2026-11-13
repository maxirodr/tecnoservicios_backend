<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Tests\Feature\Obras\Support\ObrasTestCase;

/**
 * TL-01 (seguridad.md §3): denegado, IDOR y alcance "solo sus obras" de las rutas que no los tenían
 * probados. Obra ajena con alcance "propias" en `obras` = 404, igual que una inexistente; obra visible
 * pero sin la función con alcance sobre ella = 403.
 */
final class AutorizacionPorRutaTest extends ObrasTestCase
{
    public function test_historial_sin_obras_ver_da_403(): void
    {
        $obra = $this->obraDe($this->laura());

        $this->actingAs($this->sinAcceso())->getJson("/api/obras/{$obra->id}/historial")->assertForbidden();
    }

    public function test_pedidos_abiertos_sin_obras_ver_da_403(): void
    {
        $obra = $this->obraDe($this->laura());

        $this->actingAs($this->sinAcceso())->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertForbidden();
    }

    public function test_pedidos_abiertos_de_obra_ajena_con_alcance_propias_da_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $laura = $this->laura();
        $ajena = $this->obraDe($this->diego());

        $this->actingAs($laura)->getJson("/api/obras/{$ajena->id}/pedidos-abiertos")->assertNotFound();
        $this->actingAs($laura)->getJson("/api/obras/{$this->obraDe($laura)->id}/pedidos-abiertos")->assertOk();
    }

    public function test_sugerir_numero_sin_obras_ver_da_403(): void
    {
        $obra = $this->obraDe($this->laura());

        $this->actingAs($this->sinAcceso())->getJson("/api/obras/{$obra->id}/presupuestos/sugerir-numero")->assertForbidden();
    }

    public function test_sugerir_numero_de_obra_ajena_con_alcance_propias_da_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $laura = $this->laura();
        $ajena = $this->obraDe($this->diego());

        $this->actingAs($laura)->getJson("/api/obras/{$ajena->id}/presupuestos/sugerir-numero")->assertNotFound();
        $this->actingAs($laura)->getJson("/api/obras/{$this->obraDe($laura)->id}/presupuestos/sugerir-numero")->assertOk();
    }

    public function test_clientes_con_alcance_propias_solo_sugiere_los_de_sus_obras(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $laura = $this->laura();
        Obra::factory()->deResponsable($laura)->create(['cliente' => 'Planta Ejemplo SA']);
        Obra::factory()->deResponsable($this->diego())->create(['cliente' => 'Terminal Sur SA']);

        $this->actingAs($laura)->getJson('/api/obras/clientes')->assertOk()->assertJsonPath('data', ['Planta Ejemplo SA']);
        $this->actingAs($laura)->getJson('/api/obras/clientes?buscar=terminal')->assertOk()->assertJsonPath('data', []);
    }

    public function test_montos_de_obra_ajena_con_presupuesto_rubro_editar_propias_da_403(): void
    {
        $this->celda('Compras', 'presupuesto_rubro', 'editar', 'propias');
        $carla = $this->carla();
        $ajeno = $this->principalDe($this->obraDe($this->diego()));
        $propio = $this->principalDe($this->obraDe($carla));

        $this->actingAs($carla)->putJson("/api/presupuestos/{$ajeno->id}/montos", ['modo' => 'solo_total', 'total' => '1.00'])
            ->assertForbidden();
        $this->assertSame('sin_cargar', $ajeno->refresh()->modo_carga->value);
        $this->actingAs($carla)->putJson("/api/presupuestos/{$propio->id}/montos", ['modo' => 'solo_total', 'total' => '1.00'])
            ->assertOk();
    }

    public function test_montos_de_obra_ajena_con_obras_propias_da_404(): void
    {
        $this->celda('Compras', 'obras', 'ver', 'propias');
        $ajeno = $this->principalDe($this->obraDe($this->diego()));

        $this->actingAs($this->carla())->putJson("/api/presupuestos/{$ajeno->id}/montos", ['modo' => 'solo_total', 'total' => '1.00'])
            ->assertNotFound();
    }

    public function test_monto_obra_de_obra_ajena_con_monto_obra_editar_propias_da_403(): void
    {
        $this->celda('Jefatura de Administración', 'monto_obra', 'editar', 'propias');
        $diego = $this->diego();
        $ajeno = $this->principalDe($this->obraDe($this->laura()));
        $propio = $this->principalDe($this->obraDe($diego));

        $this->actingAs($diego)->putJson("/api/presupuestos/{$ajeno->id}/monto-obra", ['monto_obra' => '1.00'])->assertForbidden();
        $this->assertNull($ajeno->refresh()->monto_obra);
        $this->actingAs($diego)->putJson("/api/presupuestos/{$propio->id}/monto-obra", ['monto_obra' => '1.00'])
            ->assertOk()
            ->assertJsonPath('data.monto_obra', '1.00');
    }

    public function test_monto_obra_de_obra_ajena_con_obras_propias_da_404(): void
    {
        $this->celda('Jefatura de Administración', 'obras', 'editar', 'propias');
        $ajeno = $this->principalDe($this->obraDe($this->laura()));

        $this->actingAs($this->diego())->putJson("/api/presupuestos/{$ajeno->id}/monto-obra", ['monto_obra' => '1.00'])
            ->assertNotFound();
    }

    private function principalDe(Obra $obra): Presupuesto
    {
        return Presupuesto::query()->where('obra_id', $obra->id)->sole();
    }
}
