<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Gastos\Domain\Events\GastoAnulado;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use Tests\Feature\Rendicion\Support\CasoDeReferencia;
use Tests\Feature\Rendicion\Support\RendicionTestCase;

/**
 * Test de aceptación de la semana 3 (rendicion.md §9, backlog §7.1): con los 21 renglones de §9.2 tal como los
 * devuelve `GastadoDeObras`, cada número de W-11, W-36, W-37, W-48, W-12 y W-05 da exacto, al centavo.
 */
final class RendicionCasoDeReferenciaTest extends RendicionTestCase
{
    use CasoDeReferencia;

    /** §9.3: rubro => [presupuestado, gastado, saldo, %, pasado, sin presupuesto]. */
    private const W11 = [
        'materiales' => ['5000000.00', '2701827.63', '2298172.37', '54.04', false, false],
        'insumos' => ['800000.00', '130333.30', '669666.70', '16.29', false, false],
        'subcontratos' => ['1200000.00', '700000.00', '500000.00', '58.33', false, false],
        'logistica' => ['300000.00', '328000.00', '-28000.00', '109.33', true, false],
        'amortizacion' => ['200000.00', '190000.00', '10000.00', '95.00', false, false],
        'mantenimiento' => ['0.00', '35000.00', '-35000.00', null, true, true],
        'obradores_banos' => ['150000.00', '0.00', '150000.00', '0.00', false, false],
        'servicios_generales' => ['0.00', '0.00', '0.00', null, false, false],
        'maquinas_herramientas' => ['400000.00', '0.00', '400000.00', '0.00', false, false],
        'viaticos' => ['600000.00', '42000.00', '558000.00', '7.00', false, false],
        'fijos' => ['500000.00', '520000.00', '-20000.00', '104.00', true, false],
        'horas_liquidadas' => ['2000000.00', '2080000.75', '-80000.75', '104.00', true, false],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cargarCasoDeReferencia();
    }

    public function test_caso_de_referencia_da_cada_rubro_al_centavo(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertOk()->json('data');

        $this->assertSame(array_keys(self::W11), array_column($data['rubros'], 'clave'), 'Los 12 rubros, en el orden de su hoja General.');
        foreach ($data['rubros'] as $fila) {
            [$presupuestado, $gastado, $saldo, $porcentaje, $pasado, $sinPresupuesto] = self::W11[$fila['clave']];
            $this->assertSame(
                compact('presupuestado', 'gastado', 'saldo', 'porcentaje', 'pasado', 'sinPresupuesto'),
                ['presupuestado' => $fila['presupuestado'], 'gastado' => $fila['gastado'], 'saldo' => $fila['saldo'], 'porcentaje' => $fila['porcentaje'], 'pasado' => $fila['pasado'], 'sinPresupuesto' => $fila['sin_presupuesto']],
                "Rubro {$fila['clave']}",
            );
        }

        $this->assertSame(['epp'], $data['rubros'][1]['incluye']);
        $this->assertSame('mixta', $data['presupuesto']['situacion']);
        $this->assertSame(['presupuestado' => '1000000.00', 'presupuestos' => ['1-200-2026 A1']], $data['sin_desglose']);
        $this->assertSame(['presupuestado' => '12150000.00', 'gastado' => '6727161.68', 'saldo' => '5422838.32', 'porcentaje' => '55.37', 'pasado' => false, 'rubros_pasados' => 4], $data['total']);
    }

    public function test_monto_y_ganancias_con_monto_obra_ver(): void
    {
        $monto = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertOk()->json('data.monto');

        $this->assertSame([
            'monto_obra' => '16300000.00',
            'por_presupuesto' => [['numero' => '1-200-2026', 'monto' => '15000000.00'], ['numero' => '1-200-2026 A1', 'monto' => '1300000.00']],
            'sin_monto' => [],
            'ganancia_prevista' => '4150000.00', 'porcentaje_prevista' => '25.46',
            'ganancia_al_dia' => '9572838.32', 'porcentaje_al_dia' => '58.73',
        ], $monto);
    }

    public function test_monto_no_viaja_sin_permiso(): void
    {
        $data = $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertOk()->json('data');

        $this->assertArrayNotHasKey('monto', $data);
        $this->assertSame('6727161.68', $data['total']['gastado']);
    }

    public function test_no_suman_el_sin_obra_el_anulado_ni_el_de_otra_obra_y_los_ultimos_son_diez_por_fecha(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertOk()->json('data');

        $this->assertSame([921, 920, 919, 917, 916, 914, 913, 912, 918, 911], array_column($data['ultimos_gastos'], 'renglon_id'));
        $this->assertSame(['renglon_id' => 921, 'gasto_id' => 315, 'fecha' => '2026-11-30', 'documento' => 'Período 1 al 30-nov', 'proveedor' => null,
            'concepto' => 'Fijos 1 al 30-nov', 'empleado' => null, 'rubro' => ['clave' => 'fijos', 'nombre' => 'FIJOS'], 'total' => '260000.00'], $data['ultimos_gastos'][0]);
        $this->assertNotContains(904, array_column($data['ultimos_gastos'], 'renglon_id'));
    }

    public function test_detalle_de_materiales_ordena_por_fecha_y_el_pie_es_el_gastado(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion/rubros/materiales")->assertOk()->json('data');

        $this->assertSame([916, 909, 908, 905, 901], array_column($data['renglones'], 'renglon_id'));
        $this->assertSame(['Devolución', 'Retiro · remito 0001-00000077', 'Devolución', 'Factura 0004-00000020', 'Factura 0001-00000101'], array_column($data['renglones'], 'documento'));
        $this->assertSame([null, 'Tecnobahía', 'Corralón Ejemplo', 'Hormigonera Ejemplo', 'Corralón Ejemplo'], array_column($data['renglones'], 'proveedor'));
        $this->assertSame(['12.500', '101234.57', '1265432.13'], [$data['renglones'][3]['cantidad'], $data['renglones'][3]['precio_unitario'], $data['renglones'][3]['total']]);
        $this->assertSame([5, '2701827.63', '5000000.00', '2298172.37', '54.04'], [$data['cantidad_renglones'], $data['gastado'], $data['presupuestado'], $data['saldo'], $data['porcentaje']]);
    }

    public function test_detalle_de_insumos_marca_epp_y_subtotal(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion/rubros/insumos")->assertOk()->json('data');

        $this->assertSame([912, 903, 902], array_column($data['renglones'], 'renglon_id'), 'A igual fecha, el cargado último primero; el tornillo anulado no aparece.');
        $this->assertSame(['epp', 'insumos', 'epp'], array_column($data['renglones'], 'rubro_clave'));
        $this->assertSame(['130333.30', '87000.00', 3], [$data['gastado'], $data['subtotal_epp'], $data['cantidad_renglones']]);
    }

    public function test_detalle_de_horas_liquidadas_muestra_los_periodos(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion/rubros/horas_liquidadas")->assertOk()->json('data');

        $this->assertSame(['Período 16 al 31-oct', 'Período 1 al 15-oct'], array_column($data['renglones'], 'documento'));
        $this->assertSame('2080000.75', $data['gastado']);
    }

    public function test_por_proveedor_da_seis_grupos_y_cuatro_comprobantes(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion/por-proveedor")->assertOk()->json('data');

        $this->assertSame(
            [['Hormigonera Ejemplo', '1525432.13', 1], ['Corralón Ejemplo', '1041130.80', 1], ['Subcontratista Ejemplo', '700000.00', 1], ['Tecnobahía', '540000.00', null], ['Taller Ejemplo', '35000.00', 1], [null, '2885598.75', null]],
            array_map(static fn (array $g): array => [$g['proveedor']['nombre'] ?? null, $g['total'], $g['comprobantes']], $data['grupos']),
        );
        $this->assertTrue($data['grupos'][5]['sin_documento']);
        $this->assertSame(
            [['Cemento x 50 kg', '100.000', '985050.00'], ['Guantes de trabajo (par)', '20.000', '62000.00'], ['Disco de corte 230 mm', '10.000', '43333.30'], ['Cemento x 50 kg (devuelto al corralón)', '-5.000', '-49252.50']],
            array_map(static fn (array $c): array => array_values($c), $data['grupos'][1]['conceptos']),
        );
        $this->assertSame(['6727161.68', 4, 6], [$data['total'], $data['comprobantes'], $data['cantidad_grupos']]);
    }

    public function test_por_proveedor_filtra_por_periodo_y_por_rubro(): void
    {
        $url = "/api/obras/{$this->ampliacion->id}/rendicion/por-proveedor";
        $noviembre = $this->actingAs($this->diego())->getJson("{$url}?desde=2026-11-01&hasta=2026-11-30")->assertOk()->json('data');
        $this->assertSame(['260000.00', 1], [$noviembre['total'], $noviembre['cantidad_grupos']]);
        $this->assertSame([['concepto' => 'Fijos 1 al 30-nov', 'cantidad' => '1.000', 'total' => '260000.00']], $noviembre['grupos'][0]['conceptos']);
        $this->assertSame(['rubro' => null, 'desde' => '2026-11-01', 'hasta' => '2026-11-30'], $noviembre['filtros']);

        $insumos = $this->getJson("{$url}?rubro=insumos")->assertOk()->json('data');
        $this->assertSame([['Corralón Ejemplo', '105333.30', 1], [null, '25000.00', null]], array_map(static fn (array $g): array => [$g['proveedor']['nombre'] ?? null, $g['total'], $g['comprobantes']], $insumos['grupos']));
        $this->assertSame('130333.30', $insumos['total']);
    }

    public function test_control_por_cantidad_da_la_lista_de_la_referencia(): void
    {
        $data = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/control-cantidad")->assertOk()->json('data');

        $this->assertSame(['fechas' => ['2026-10-02', '2026-10-08', '2026-10-14', '2026-10-20'], 'hasta' => null], $data['columnas']);
        $this->assertSame(
            [
                ['Cemento x 50 kg', '150.000', ['2026-10-02' => '100.000', '2026-10-20' => '-4.000'], '96.000', '54.000', '9800.00', '945648.00'],
                ['Hormigón H-21', '40.000', ['2026-10-08' => '12.500'], '12.500', '27.500', '100000.00', '1265432.13'],
                ['Endurecedor superficial', '50.000', ['2026-10-14' => '30.000'], '30.000', '20.000', '18000.00', '540000.00'],
                ['Malla sima', '30.000', [], '0.000', '30.000', '21000.00', '0.00'],
            ],
            array_map(static fn (array $m): array => [$m['nombre'], $m['cantidad_presupuestada'], $m['retirado_por_fecha'], $m['total_retirado'], $m['resta_llevar'], $m['precio_presupuestado'], $m['total']], $data['materiales']),
        );
        $this->assertSame('1-200-2026', $data['materiales'][0]['presupuesto']['numero']);
        $this->assertSame('2751080.13', $data['total_lista']);
        $this->assertSame(['gastado' => '2701827.63', 'sin_material' => '-49252.50'], $data['materiales_en_rendicion']);
        $this->assertSame([['Guantes de trabajo', 'par', '2026-10-02', '20.000', '62000.00', 'epp']], array_map(
            static fn (array $f): array => [$f['nombre'], $f['unidad'], $f['fecha'], $f['cantidad'], $f['total'], $f['rubro']['clave']],
            $data['fuera_de_presupuesto'],
        ));
    }

    public function test_rendiciones_en_curso_y_porcentaje_del_listado(): void
    {
        $data = $this->actingAs($this->diego())->getJson('/api/rendiciones')->assertOk()->json('data');

        $this->assertSame(
            [[$this->ampliacion->id, 'Planta Ejemplo SA', '12150000.00', '6727161.68', '55.37', false, 4], [$this->control->id, 'Cliente Control SA', '1000000.00', '300000.00', '30.00', false, 1]],
            array_map(static fn (array $f): array => [$f['obra']['id'], $f['cliente'], $f['presupuestado'], $f['gastado'], $f['porcentaje'], $f['pasado'], $f['rubros_pasados']], $data['obras']),
        );
        $this->assertSame(['presupuestado' => '13150000.00', 'gastado' => '7027161.68', 'porcentaje' => '53.44', 'rubros_pasados' => 5], $data['total']);

        $avance = $this->app->make(AvanceGastoDeObra::class);
        $this->assertSame([$this->ampliacion->id => '55.37', $this->control->id => '30.00'], $avance->porcentajesGastados([$this->ampliacion->id, $this->control->id]));
        $this->assertSame([$this->ampliacion->id => false, $this->control->id => false], $avance->pasadas([$this->ampliacion->id, $this->control->id]));
    }

    public function test_laura_ve_solo_su_obra_en_curso_y_el_total_de_esa_fila(): void
    {
        $data = $this->actingAs($this->responsableAmpliacion)->getJson('/api/rendiciones')->assertOk()->json('data');

        $this->assertSame([$this->ampliacion->id], array_column(array_column($data['obras'], 'obra'), 'id'));
        $this->assertSame(['presupuestado' => '12150000.00', 'gastado' => '6727161.68', 'porcentaje' => '55.37', 'rubros_pasados' => 4], $data['total']);
    }

    public function test_anular_la_factura_de_la_hormigonera_baja_materiales_y_logistica_en_la_proxima_lectura(): void
    {
        $diego = $this->diego();
        $url = "/api/obras/{$this->ampliacion->id}/rendicion";
        $this->actingAs($diego)->getJson($url)->assertOk()->assertJsonPath('data.total.gastado', '6727161.68');

        event(new GastoAnulado(self::FACTURA_HORMIGONERA, $this->gastado->anular(self::FACTURA_HORMIGONERA)));

        $data = $this->getJson($url)->assertOk()->json('data');
        $this->assertSame(['1436395.50', false], [$data['rubros'][0]['gastado'], $data['rubros'][0]['pasado']]);
        $this->assertSame(['68000.00', false], [$data['rubros'][3]['gastado'], $data['rubros'][3]['pasado']]);
        $this->assertSame(['5201729.55', '42.81', 3], [$data['total']['gastado'], $data['total']['porcentaje'], $data['total']['rubros_pasados']]);
        $control = $this->getJson("/api/obras/{$this->ampliacion->id}/control-cantidad")->assertOk()->json('data');
        $this->assertSame(['0.000', '40.000'], [$control['materiales'][1]['total_retirado'], $control['materiales'][1]['resta_llevar']]);
    }
}
