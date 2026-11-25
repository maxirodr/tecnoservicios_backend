<?php

declare(strict_types=1);

namespace Tests\Feature\Integracion;

use App\Modules\Identidad\Domain\Models\User;
use Tests\Feature\Integracion\Support\CasoDeReferenciaPorApi;
use Tests\Feature\Integracion\Support\IntegracionTestCase;

/**
 * Test de aceptación de la semana 3 de punta a punta (rendicion.md §9, backlog §7.1, tanda 2): el caso de
 * referencia cargado por las API de Obras, Rendición y Gastos, y leído por la API de Rendición, da §9.3 y §9.4
 * al centavo. Al anular la 0004-00000020 la rendición se actualiza sola (eventos de Gastos → cache de Rendición).
 */
final class CasoDeReferenciaDePuntaAPuntaTest extends IntegracionTestCase
{
    use CasoDeReferenciaPorApi;

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

    private User $diego;

    private User $laura;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diego = $this->diego();
        $this->laura = $this->laura();
        $this->cargarCasoDeReferenciaPorApi($this->diego, $this->laura, $this->hernan());
    }

    public function test_el_caso_de_referencia_cargado_por_la_api_da_la_rendicion_al_centavo_y_se_actualiza_sola_al_anular(): void
    {
        $this->rendicionDeLaObraDaW11();
        $this->detallesDeRubroDanW36();
        $this->porProveedorDaW37();
        $this->controlPorCantidadDaW48();
        $this->enCursoYListadoDanW12yW05();

        // §9.4 "Anular": sin limpiar nada a mano, en la próxima lectura.
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->facturaHormigonera}/anular", ['motivo' => 'Prueba del caso de referencia'])->assertOk();

        $data = $this->rendicion($this->diego);
        $rubros = array_column($data['rubros'], null, 'clave');
        $this->assertSame(['1436395.50', false], [$rubros['materiales']['gastado'], $rubros['materiales']['pasado']]);
        $this->assertSame(['68000.00', false], [$rubros['logistica']['gastado'], $rubros['logistica']['pasado']]);
        $this->assertSame(['5201729.55', '42.81', 3], [$data['total']['gastado'], $data['total']['porcentaje'], $data['total']['rubros_pasados']]);
        $hormigon = $this->getJson("/api/obras/{$this->ampliacion}/control-cantidad")->assertOk()->json('data.materiales.1');
        $this->assertSame(['Hormigón H-21', '0.000', '40.000'], [$hormigon['nombre'], $hormigon['total_retirado'], $hormigon['resta_llevar']]);
        $this->getJson('/api/rendiciones')->assertOk()->assertJsonPath('data.obras.0.gastado', '5201729.55');
        $this->getJson('/api/obras?estado=en_ejecucion')->assertOk()->assertJsonPath('data.0.porcentaje_gastado', '42.81');
    }

    private function rendicionDeLaObraDaW11(): void
    {
        $data = $this->rendicion($this->diego);

        $this->assertSame(array_keys(self::W11), array_column($data['rubros'], 'clave'));
        foreach ($data['rubros'] as $fila) {
            $this->assertSame(
                self::W11[$fila['clave']],
                [$fila['presupuestado'], $fila['gastado'], $fila['saldo'], $fila['porcentaje'], $fila['pasado'], $fila['sin_presupuesto']],
                "Rubro {$fila['clave']}",
            );
        }
        $this->assertSame(['epp'], $data['rubros'][1]['incluye']);
        $this->assertSame('mixta', $data['presupuesto']['situacion']);
        $this->assertSame(['presupuestado' => '1000000.00', 'presupuestos' => ['1-200-2026 A1']], $data['sin_desglose']);
        $this->assertSame(['presupuestado' => '12150000.00', 'gastado' => '6727161.68', 'saldo' => '5422838.32', 'porcentaje' => '55.37', 'pasado' => false, 'rubros_pasados' => 4], $data['total']);
        $this->assertSame([
            'monto_obra' => '16300000.00',
            'por_presupuesto' => [['numero' => '1-200-2026', 'monto' => '15000000.00'], ['numero' => '1-200-2026 A1', 'monto' => '1300000.00']],
            'sin_monto' => [],
            'ganancia_prevista' => '4150000.00', 'porcentaje_prevista' => '25.46',
            'ganancia_al_dia' => '9572838.32', 'porcentaje_al_dia' => '58.73',
        ], $data['monto']);
        $this->assertSame('Fijos 1 al 30-nov', $data['ultimos_gastos'][0]['concepto']);
        $this->assertNotContains('Flete', array_column($data['ultimos_gastos'], 'concepto'), 'El renglón sin obra no suma.');

        $deLaura = $this->rendicion($this->laura);
        $this->assertArrayNotHasKey('monto', $deLaura, 'Sin monto_obra el JSON no trae monto.');
        $this->assertSame('6727161.68', $deLaura['total']['gastado']);
    }

    private function detallesDeRubroDanW36(): void
    {
        $url = "/api/obras/{$this->ampliacion}/rendicion/rubros";
        $materiales = $this->actingAs($this->diego)->getJson("{$url}/materiales")->assertOk()->json('data');
        $this->assertSame(
            [
                ['2026-10-20', 'Devolución', null, 'Cemento x 50 kg (sobrante)', '-4.000', '9850.50', '-39402.00'],
                ['2026-10-14', 'Retiro · remito 0001-00000077', 'Tecnobahía', 'Endurecedor superficial (bolsa)', '30.000', '18000.00', '540000.00'],
                ['2026-10-12', 'Devolución', 'Corralón Ejemplo', 'Cemento x 50 kg (devuelto al corralón)', '-5.000', '9850.50', '-49252.50'],
                ['2026-10-08', 'Factura 0004-00000020', 'Hormigonera Ejemplo', 'Hormigón H-21 (m³)', '12.500', '101234.57', '1265432.13'],
                ['2026-10-02', 'Factura 0001-00000101', 'Corralón Ejemplo', 'Cemento x 50 kg', '100.000', '9850.50', '985050.00'],
            ],
            array_map(static fn (array $r): array => [$r['fecha'], $r['documento'], $r['proveedor'], $r['concepto'], $r['cantidad'], $r['precio_unitario'], $r['total']], $materiales['renglones']),
        );
        $this->assertSame([5, '2701827.63'], [$materiales['cantidad_renglones'], $materiales['gastado']]);

        $insumos = $this->getJson("{$url}/insumos")->assertOk()->json('data');
        $this->assertSame(
            [['Antiparras', 'epp'], ['Disco de corte 230 mm', 'insumos'], ['Guantes de trabajo (par)', 'epp']],
            array_map(static fn (array $r): array => [$r['concepto'], $r['rubro_clave']], $insumos['renglones']),
            'A igual fecha, el cargado último primero; el tornillo anulado no aparece.',
        );
        $this->assertSame(['130333.30', '87000.00', 3], [$insumos['gastado'], $insumos['subtotal_epp'], $insumos['cantidad_renglones']]);

        $horas = $this->getJson("{$url}/horas_liquidadas")->assertOk()->json('data');
        $this->assertSame([['Período 16 al 31-oct', '1130000.75'], ['Período 1 al 15-oct', '950000.00']], array_map(static fn (array $r): array => [$r['documento'], $r['total']], $horas['renglones']));
        $this->assertSame('2080000.75', $horas['gastado']);
    }

    private function porProveedorDaW37(): void
    {
        $url = "/api/obras/{$this->ampliacion}/rendicion/por-proveedor";
        $grupos = static fn (array $data): array => array_map(static fn (array $g): array => [$g['proveedor']['nombre'] ?? null, $g['total'], $g['comprobantes']], $data['grupos']);

        $todo = $this->actingAs($this->diego)->getJson($url)->assertOk()->json('data');
        $this->assertSame(
            [['Hormigonera Ejemplo', '1525432.13', 1], ['Corralón Ejemplo', '1041130.80', 1], ['Subcontratista Ejemplo', '700000.00', 1], ['Tecnobahía', '540000.00', null], ['Taller Ejemplo', '35000.00', 1], [null, '2885598.75', null]],
            $grupos($todo),
        );
        $this->assertTrue($todo['grupos'][5]['sin_documento']);
        $this->assertSame(['6727161.68', 4, 6], [$todo['total'], $todo['comprobantes'], $todo['cantidad_grupos']]);

        $noviembre = $this->getJson("{$url}?desde=2026-11-01&hasta=2026-11-30")->assertOk()->json('data');
        $this->assertSame([[null, '260000.00', null]], $grupos($noviembre));
        $this->assertSame('Fijos 1 al 30-nov', $noviembre['grupos'][0]['conceptos'][0]['concepto']);

        $insumos = $this->getJson("{$url}?rubro=insumos")->assertOk()->json('data');
        $this->assertSame([['Corralón Ejemplo', '105333.30', 1], [null, '25000.00', null]], $grupos($insumos));
        $this->assertSame('130333.30', $insumos['total']);
    }

    private function controlPorCantidadDaW48(): void
    {
        $data = $this->actingAs($this->diego)->getJson("/api/obras/{$this->ampliacion}/control-cantidad")->assertOk()->json('data');

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
        $this->assertSame('2751080.13', $data['total_lista']);
        $this->assertSame(['gastado' => '2701827.63', 'sin_material' => '-49252.50'], $data['materiales_en_rendicion']);
        $this->assertSame([['Guantes de trabajo', 'par', '2026-10-02', '20.000', '62000.00', 'epp']], array_map(
            static fn (array $f): array => [$f['nombre'], $f['unidad'], $f['fecha'], $f['cantidad'], $f['total'], $f['rubro']['clave']],
            $data['fuera_de_presupuesto'],
        ));
    }

    private function enCursoYListadoDanW12yW05(): void
    {
        $data = $this->actingAs($this->diego)->getJson('/api/rendiciones')->assertOk()->json('data');
        $this->assertSame(
            [[$this->ampliacion, 'Planta Ejemplo SA', '12150000.00', '6727161.68', '55.37', false, 4], [$this->control, 'Cliente Control SA', '1000000.00', '300000.00', '30.00', false, 1]],
            array_map(static fn (array $f): array => [$f['obra']['id'], $f['cliente'], $f['presupuestado'], $f['gastado'], $f['porcentaje'], $f['pasado'], $f['rubros_pasados']], $data['obras']),
        );
        $this->assertSame(['presupuestado' => '13150000.00', 'gastado' => '7027161.68', 'porcentaje' => '53.44', 'rubros_pasados' => 5], $data['total']);

        $listado = $this->getJson('/api/obras?estado=en_ejecucion')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(
            [[$this->ampliacion, '55.37'], [$this->control, '30.00']],
            array_map(static fn (array $f): array => [$f['id'], $f['porcentaje_gastado']], $listado),
        );

        $deLaura = $this->actingAs($this->laura)->getJson('/api/rendiciones')->assertOk()->json('data');
        $this->assertSame([$this->ampliacion], array_column(array_column($deLaura['obras'], 'obra'), 'id'));
        $this->assertSame(['presupuestado' => '12150000.00', 'gastado' => '6727161.68', 'porcentaje' => '55.37', 'rubros_pasados' => 4], $deLaura['total']);
    }

    /** @return array<string, mixed> */
    private function rendicion(User $user): array
    {
        $data = $this->actingAs($user)->getJson("/api/obras/{$this->ampliacion}/rendicion")->assertOk()->json('data');
        $this->assertIsArray($data);

        return $data;
    }
}
