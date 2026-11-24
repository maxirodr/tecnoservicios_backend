<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Obras\Domain\Models\Obra;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-45 · HU-071 · W-33: gastos cargados, filtros, total de lo filtrado y agrupar (RN-GAS-29…35). */
final class GastosCargadosTest extends GastosTestCase
{
    private Obra $nave;

    private Obra $losa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nave = $this->obra('Piso industrial nave 3');
        $this->losa = $this->obra('Reparación de losa tanque B');
    }

    private function facturaDeCorralon(): int
    {
        $id = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa))->id;

        return $id;
    }

    public function test_listado_ordena_por_fecha_desc(): void
    {
        $diego = $this->diego();
        $vieja = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-1', 'fecha' => '2026-10-02']));
        $nueva = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-2', 'fecha' => '2026-10-17']));
        $mismaFecha = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-3', 'fecha' => '2026-10-17']));

        $this->actingAs($this->marta())->getJson('/api/gastos')->assertOk()
            ->assertJsonPath('data.0.id', $mismaFecha->id)
            ->assertJsonPath('data.1.id', $nueva->id)
            ->assertJsonPath('data.2.id', $vieja->id)
            ->assertJsonPath('data.0.documento', 'Factura 0001-00000003')
            ->assertJsonPath('data.0.cargado_por.nombre', 'Diego')
            ->assertJsonPath('data.0.obras', [['id' => $this->nave->id, 'nombre_trabajo' => 'Piso industrial nave 3'], ['id' => $this->losa->id, 'nombre_trabajo' => 'Reparación de losa tanque B'], null])
            ->assertJsonPath('data.0.rubros', ['materiales', 'maquinas_herramientas', 'insumos', 'logistica'])
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_fecha_por_defecto_es_el_mes_en_curso(): void
    {
        $diego = $this->diego();
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-1', 'fecha' => '2026-09-30']));
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-2', 'fecha' => '2026-10-01']));

        $this->actingAs($diego)->getJson('/api/gastos')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.desde', '2026-10-01')->assertJsonPath('meta.hasta', self::HOY);
        $this->actingAs($diego)->getJson("/api/gastos?obra_id={$this->nave->id}&desde=&hasta=")->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.desde', null);
    }

    public function test_filtros_se_combinan(): void
    {
        $diego = $this->diego();
        $this->facturaDeCorralon();
        $ferreteria = $this->proveedor('Ferretería Norte');
        $this->cargado($diego, ['origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura',
            'renglones' => [$this->renglon('Peaje', '1', '3500', $this->losa, 'logistica')]]);
        $this->cargado($diego, ['origen' => 'factura', 'numero' => '9-9', 'fecha' => '2026-10-10', 'proveedor_id' => $ferreteria->id,
            'renglones' => [$this->renglon('Tornillos', '100', '20', $this->nave, 'insumos')]]);

        $this->actingAs($diego)->getJson("/api/gastos?proveedor_id={$ferreteria->id}")->assertJsonCount(1, 'data')->assertJsonPath('meta.total_filtrado', '2000.00');
        $this->actingAs($diego)->getJson('/api/gastos?tipo=sin_documento')->assertJsonCount(1, 'data')->assertJsonPath('data.0.proveedor.es_generico', true);
        $this->actingAs($diego)->getJson('/api/gastos?rubro=logistica&tipo=factura')->assertJsonCount(1, 'data')->assertJsonPath('meta.total_filtrado', '45000.00');
        $this->actingAs($diego)->getJson('/api/gastos?obra_id=sin_obra')->assertJsonCount(1, 'data')->assertJsonPath('data.0.total_filtrado', '45000.00');
        $this->actingAs($diego)->getJson('/api/gastos?desde=2026-10-15&hasta=2026-10-17')->assertJsonCount(1, 'data');
        $this->actingAs($diego)->getJson("/api/gastos?obra_id={$this->losa->id}&rubro=logistica")->assertJsonCount(1, 'data')->assertJsonPath('meta.total_filtrado', '3500.00');
        $this->actingAs($diego)->getJson('/api/gastos?rubro=no_existe')->assertUnprocessable();
    }

    public function test_filtro_por_obra_suma_solo_sus_renglones(): void
    {
        $this->facturaDeCorralon();

        $this->actingAs($this->marta())->getJson("/api/gastos?obra_id={$this->nave->id}&desde=2026-10-01&hasta=2026-10-19")->assertOk()
            ->assertJsonPath('data.0.total', '905600.00')
            ->assertJsonPath('data.0.total_filtrado', '549000.00')
            ->assertJsonPath('meta.total_filtrado', '549000.00')
            ->assertJsonPath('meta.comprobantes', 1);
    }

    public function test_total_de_lo_filtrado_es_de_todo_no_de_la_pagina(): void
    {
        $diego = $this->diego();
        $renglones = [$this->renglon('Tornillo', '1', '10', $this->nave, 'insumos')];

        for ($i = 1; $i <= 51; $i++) {
            $this->cargado($diego, ['origen' => 'consumo_stock', 'fecha' => '2026-10-18', 'renglones' => $renglones]);
        }

        $this->actingAs($diego)->getJson('/api/gastos')->assertOk()->assertJsonCount(50, 'data')
            ->assertJsonPath('meta.total', 51)
            ->assertJsonPath('meta.comprobantes', 51)
            ->assertJsonPath('meta.total_filtrado', '510.00');
    }

    public function test_agrupar_por_obra_reparte_la_factura(): void
    {
        $this->facturaDeCorralon();

        $grupos = $this->actingAs($this->marta())->getJson('/api/gastos?agrupar=obra')->assertOk()->json('meta.grupos');
        $this->assertIsArray($grupos);
        $this->assertSame(['Piso industrial nave 3', 'Reparación de losa tanque B', '(sin obra)'], array_column($grupos, 'nombre'));
        $this->assertSame(['549000.00', '311600.00', '45000.00'], array_column($grupos, 'subtotal'));
        $this->assertSame('549000.00', $grupos[0]['filas'][0]['total_filtrado']);
        $this->assertSame(1, $grupos[2]['comprobantes']);
        $this->assertNull($grupos[2]['clave']);
    }

    public function test_agrupar_por_rubro_deja_epp_aparte(): void
    {
        $datos = $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa);
        $datos['renglones'][1]['rubro'] = 'epp';
        $this->cargado($this->diego(), $datos);

        $grupos = $this->actingAs($this->marta())->getJson('/api/gastos?agrupar=rubro')->json('meta.grupos');
        $this->assertIsArray($grupos);
        $this->assertContains('epp', array_column($grupos, 'clave'));
        $this->assertContains('insumos', array_column($grupos, 'clave'));
        $this->assertSame('255000.00', $grupos[array_search('epp', array_column($grupos, 'clave'), true)]['subtotal']);

        $porProveedor = $this->actingAs($this->marta())->getJson('/api/gastos?agrupar=proveedor')->json('meta.grupos');
        $this->assertIsArray($porProveedor);
        $this->assertSame('905600.00', $porProveedor[0]['subtotal']);
    }

    public function test_anulado_se_lista_tachado_y_no_suma(): void
    {
        $diego = $this->diego();
        $anulada = $this->facturaDeCorralon();
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '1-2']));
        $this->actingAs($diego)->postJson("/api/gastos/{$anulada}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->actingAs($diego)->getJson('/api/gastos')->assertOk()
            ->assertJsonPath('meta.comprobantes', 2)
            ->assertJsonPath('meta.anulados', 1)
            ->assertJsonPath('meta.total_filtrado', '905600.00')
            ->assertJsonPath('data.1.anulado', true);
    }

    public function test_proveedor_generico_se_muestra_como_guion(): void
    {
        $this->cargado($this->diego(), ['origen' => 'combustible', 'fecha' => '2026-10-18',
            'renglones' => [$this->renglon('Combustible', '1', '68000', $this->nave, 'logistica')]]);

        $this->actingAs($this->marta())->getJson('/api/gastos')->assertOk()
            ->assertJsonPath('data.0.proveedor.es_generico', true)
            ->assertJsonPath('data.0.documento', 'Sin documento')
            ->assertJsonPath('data.0.origen', 'combustible');
    }

    public function test_listar_pide_gastos_ver(): void
    {
        $this->getJson('/api/gastos')->assertUnauthorized();
        $this->actingAs($this->sofia())->getJson('/api/gastos')->assertForbidden();
    }
}
