<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Support\Carbon;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * El caso de referencia de rendicion.md §9.2 cargado **por la API de Gastos**: lo que `GastadoDeObras` le
 * entrega a Rendicion da exacto, al centavo, rubro por rubro (sin EPP → INSUMOS, que es de Rendicion).
 * El de Rendicion (`RendicionCasoDeReferenciaTest`) hace lo mismo con un fake; en la tanda 2 se cruzan.
 */
final class CasoDeReferenciaEnGastosTest extends GastosTestCase
{
    public function test_los_21_renglones_del_caso_de_referencia_dan_cada_rubro_al_centavo(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 13:00:00', 'UTC'));
        $diego = $this->diego();
        $ampliacion = $this->obra('Ampliación de nave de ensayo', $this->laura());
        $control = $this->obra('Obra de control');
        $anulada = $this->cargarElCaso($diego, $ampliacion, $control);

        $gastado = $this->app->make(GastadoDeObras::class);
        $esperado = [
            'materiales' => '2701827.63', 'insumos' => '43333.30', 'epp' => '87000.00', 'subcontratos' => '700000.00',
            'logistica' => '328000.00', 'amortizacion' => '190000.00', 'mantenimiento' => '35000.00', 'viaticos' => '42000.00',
            'fijos' => '520000.00', 'horas_liquidadas' => '2080000.75',
        ];
        $totales = $gastado->totalesPorRubro([$ampliacion->id, $control->id]);

        $this->assertEquals($esperado, $totales[$ampliacion->id]);
        $this->assertSame(['subcontratos' => '300000.00'], $totales[$control->id]);
        $this->assertSame('6727161.68', array_reduce($totales[$ampliacion->id], static fn (string $s, string $t): string => bcadd($s, $t, 2), '0.00'));
        $this->assertCount(18, $gastado->renglonesDeObra($ampliacion->id, new FiltroRenglones));

        // Al anular la factura 0004-00000020, MATERIALES baja a 1.436.395,50 y LOGISTICA a 68.000 (rendicion.md §7.1).
        $this->actingAs($diego)->postJson("/api/gastos/{$anulada}/anular", ['motivo' => 'Prueba del caso de referencia'])->assertOk();
        $despues = $gastado->totalesPorRubro([$ampliacion->id])[$ampliacion->id];
        $this->assertSame('1436395.50', $despues['materiales']);
        $this->assertSame('68000.00', $despues['logistica']);
    }

    /** @return int el id de la factura 0004-00000020 */
    private function cargarElCaso(User $diego, Obra $a, Obra $control): int
    {
        $cemento = $this->material($a, 'Cemento x 50 kg', 'bolsa', '9800.00');
        $hormigon = $this->material($a, 'Hormigón H-21', 'm³', '100000.00');
        $endurecedor = $this->material($a, 'Endurecedor superficial', 'bolsa', '18000.00');
        $corralon = $this->proveedor('Corralón Ejemplo');

        $this->cargado($diego, ['origen' => 'factura', 'numero' => '1-101', 'fecha' => '2026-10-02', 'proveedor_id' => $corralon->id, 'renglones' => [
            $this->renglon('Cemento x 50 kg', '100', '9850.50', $a, 'materiales', ['material_id' => $cemento->id]),
            $this->renglon('Guantes de trabajo (par)', '20', '3100.00', $a, 'epp', ['material_nuevo' => ['nombre' => 'Guantes de trabajo', 'unidad' => 'par']]),
            $this->renglon('Disco de corte 230 mm', '10', '4333.33', $a, 'insumos'),
            $this->renglon('Flete', '1', '45000.00', null, 'logistica'),
        ]]);
        $anulada = $this->cargado($diego, ['origen' => 'factura', 'numero' => '4-20', 'fecha' => '2026-10-08', 'proveedor_id' => $this->proveedor('Hormigonera Ejemplo')->id, 'renglones' => [
            $this->renglon('Hormigón H-21 (m³)', '12.5', '101234.57', $a, 'materiales', ['material_id' => $hormigon->id]),
            $this->renglon('Flete hormigón', '1', '260000.00', $a, 'logistica'),
        ]]);
        $tornillos = $this->cargado($diego, ['origen' => 'factura', 'numero' => '2-555', 'fecha' => '2026-10-10', 'proveedor_id' => $this->proveedor('Ferretería Ejemplo')->id,
            'renglones' => [$this->renglon('Tornillos autoperforantes', '500', '12.00', $a, 'insumos')]]);
        $this->actingAs($diego)->postJson("/api/gastos/{$tornillos->id}/anular", ['motivo' => 'Caso de referencia: anulada'])->assertOk();
        $this->cargado($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-12', 'proveedor_id' => $corralon->id,
            'renglones' => [$this->renglon('Cemento x 50 kg (devuelto al corralón)', '-5', '9850.50', $a, 'materiales')]]);
        $this->cargado($diego, ['origen' => 'retiro_remito', 'numero' => '1-77', 'fecha' => '2026-10-14', 'proveedor_id' => $this->proveedor('Tecnobahía')->id,
            'renglones' => [$this->renglon('Endurecedor superficial (bolsa)', '30', '18000.00', $a, 'materiales', ['material_id' => $endurecedor->id])]]);
        $this->cargado($diego, ['origen' => 'sin_comprobante', 'fecha' => '2026-10-15', 'rendido_por' => 'supervisor de obra', 'renglones' => [
            $this->renglon('Comida cuadrilla (6 personas)', '6', '7000.00', $a, 'viaticos'),
            $this->renglon('Combustible camioneta', '1', '68000.00', $a, 'logistica'),
        ]]);
        $this->cargado($diego, ['origen' => 'consumo_stock', 'fecha' => '2026-10-16', 'renglones' => [$this->renglon('Antiparras', '10', '2500.00', $a, 'epp')]]);
        $this->cargado($diego, ['origen' => 'factura', 'numero' => '3-300', 'fecha' => '2026-10-18', 'proveedor_id' => $this->proveedor('Taller Ejemplo')->id,
            'renglones' => [$this->renglon('Repuesto bomba de agua', '1', '35000.00', $a, 'mantenimiento')]]);
        $this->cargado($diego, ['origen' => 'factura', 'numero' => '7-45', 'fecha' => '2026-10-19', 'proveedor_id' => $this->proveedor('Subcontratista Ejemplo')->id, 'renglones' => [
            $this->renglon('Adelanto 50 % demolición', '1', '700000.00', $a, 'subcontratos'),
            $this->renglon('Andamio', '1', '300000.00', $control, 'subcontratos'),
        ]]);
        $this->cargado($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-20',
            'renglones' => [$this->renglon('Cemento x 50 kg (sobrante)', '-4', '9850.50', $a, 'materiales', ['material_id' => $cemento->id])]]);
        $this->cargado($diego, ['origen' => 'amortizacion', 'fecha' => '2026-10-31',
            'renglones' => [$this->renglon('Amortización de herramientas', '1', '190000.00', $a, 'amortizacion')]]);

        foreach ([
            ['2026-10-01', '2026-10-15', 'Horas liquidadas 1 al 15-oct', '950000.00', 'horas_liquidadas'],
            ['2026-10-16', '2026-10-31', 'Horas liquidadas 16 al 31-oct', '1130000.75', 'horas_liquidadas'],
            ['2026-10-01', '2026-10-31', 'Fijos 1 al 31-oct', '260000.00', 'fijos'],
            ['2026-11-01', '2026-11-30', 'Fijos 1 al 30-nov', '260000.00', 'fijos'],
        ] as [$desde, $hasta, $concepto, $importe, $rubro]) {
            $this->cargado($diego, ['origen' => 'monto_periodo', 'periodo_desde' => $desde, 'periodo_hasta' => $hasta,
                'renglones' => [$this->renglon($concepto, '1', $importe, $a, $rubro)]]);
        }

        return $anulada->id;
    }
}
