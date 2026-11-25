<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * DoD §3 y ADR-006 (QC S3, M-2): denegado e IDOR de corregir, anular e historial, y el renglón de otro
 * gasto en la corrección (gastos.md §8.2: 403 sin la celda, 404 fuera de alcance).
 */
final class DenegadosEIdorTest extends GastosTestCase
{
    private User $laura;

    private Obra $nave;

    private Gasto $ajeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laura = $this->laura();
        $this->nave = $this->obra('Piso industrial nave 3', $this->laura);
        $losa = $this->obra('Reparación de losa tanque B');
        $this->ajeno = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor('Hotel Puerto Ejemplo'), $losa, $losa, ['numero' => '5-3310']));
    }

    public function test_corregir_un_gasto_entero_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');

        $this->actingAs($this->laura)->patchJson("/api/gastos/{$this->ajeno->id}", ['numero' => '5-3311'])->assertNotFound()
            ->assertJsonPath('message', 'No encontramos este gasto.');
        $this->assertSame('0005-00003310', $this->ajeno->fresh()?->numero);
    }

    public function test_anular_sin_gastos_editar_es_403(): void
    {
        $this->actingAs($this->marta())->postJson("/api/gastos/{$this->ajeno->id}/anular", ['motivo' => 'Cargada dos veces'])->assertForbidden();
        $this->assertNull($this->ajeno->fresh()?->anulado_at);
    }

    public function test_anular_un_gasto_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');

        $this->actingAs($this->laura)->postJson("/api/gastos/{$this->ajeno->id}/anular", ['motivo' => 'Cargada dos veces'])->assertNotFound()
            ->assertJsonPath('message', 'No encontramos este gasto.');
        $this->assertNull($this->ajeno->fresh()?->anulado_at);
    }

    public function test_historial_sin_gastos_ver_es_403_no_tenes_acceso_a_gastos(): void
    {
        $this->actingAs($this->sofia())->getJson("/api/gastos/{$this->ajeno->id}/historial")->assertForbidden()
            ->assertJsonPath('message', 'No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia.');
    }

    public function test_lista_y_detalle_sin_gastos_ver_son_403_no_tenes_acceso_a_gastos(): void
    {
        $sofia = $this->sofia();

        foreach (['/api/gastos', "/api/gastos/{$this->ajeno->id}"] as $url) {
            $this->actingAs($sofia)->getJson($url)->assertForbidden()
                ->assertJsonPath('message', 'No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia.');
        }
    }

    public function test_corregir_con_un_renglon_de_otro_gasto_es_rechazado(): void
    {
        $diego = $this->diego();
        $propio = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->nave));
        $deOtro = $this->ajeno->renglones->firstOrFail();

        $this->actingAs($diego)->patchJson("/api/gastos/{$propio->id}", [
            'renglones' => [['id' => $deOtro->id, ...$this->renglon('Cemento x 50 kg', '30', '9800.00', $this->nave, 'materiales')]],
        ])->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.id', 'Renglón 1: ese renglón no es de este comprobante.'));
        $this->assertSame($this->ajeno->id, $deOtro->fresh()?->gasto_id);
        $this->assertCount(5, $propio->fresh()?->renglones ?? []);
    }
}
