<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * Lo que Gastos le garantiza a Rendicion (`GastadoDeObras`, rendicion.md §12.4.1, gastos.md §12.5): un test
 * por invariante.
 */
final class GastadoDeObrasTest extends GastosTestCase
{
    private GastadoDeObras $gastado;

    private Obra $nave;

    private Obra $losa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gastado = $this->app->make(GastadoDeObras::class);
        $this->nave = $this->obra('Piso industrial nave 3');
        $this->losa = $this->obra('Reparación de losa tanque B');
    }

    public function test_totales_por_rubro_excluye_anulados_y_sin_obra(): void
    {
        $diego = $this->diego();
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        $anulada = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '4-20']));
        $this->actingAs($diego)->postJson("/api/gastos/{$anulada->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->assertEquals([
            $this->nave->id => ['materiales' => '549000.00'],
            $this->losa->id => ['maquinas_herramientas' => '236000.00', 'insumos' => '75600.00'],
        ], $this->ordenado($this->gastado->totalesPorRubro([$this->nave->id, $this->losa->id])));
    }

    public function test_totales_por_rubro_de_varias_obras_en_una_consulta(): void
    {
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        $vacia = $this->obra('Obra de control');
        DB::enableQueryLog();

        $totales = $this->gastado->totalesPorRubro([$this->nave->id, $this->losa->id, $vacia->id]);

        $this->assertCount(1, array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains($q['query'], 'gasto_renglones')));
        $this->assertSame([], $totales[$vacia->id], 'Obra sin renglones: [] (rendicion.md §12.4.1).');
    }

    public function test_epp_se_devuelve_como_se_imputo(): void
    {
        $datos = $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa);
        $datos['renglones'][3]['rubro'] = 'epp';
        $this->cargado($this->diego(), $datos);

        $this->assertEquals(['maquinas_herramientas' => '236000.00', 'epp' => '75600.00'], $this->gastado->totalesPorRubro([$this->losa->id])[$this->losa->id]);
    }

    public function test_renglones_de_obra_filtra_y_ordena_por_fecha_efectiva(): void
    {
        $diego = $this->diego();
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        $this->cargado($diego, ['origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura', 'renglones' => [
            $this->renglon('Combustible', '1', '68000', $this->nave, 'logistica', ['fecha' => '2026-10-15']),
            $this->renglon('Peaje', '1', '3500', $this->nave, 'logistica', ['fecha' => '2026-10-18']),
        ]]);

        $todos = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones);
        $this->assertSame(['2026-10-18', '2026-10-17', '2026-10-17', '2026-10-15'], array_map(static fn (RenglonImputado $r): string => $r->fecha, $todos));

        $logistica = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones(rubroClaves: ['logistica'], desde: '2026-10-16', hasta: '2026-10-18'));
        $this->assertCount(1, $logistica);
        $this->assertSame('Peaje', $logistica[0]->concepto);
        $this->assertSame('Laura', $logistica[0]->rendidoPor);
        $this->assertTrue($logistica[0]->proveedorGenerico);
        $this->assertSame('sin_comprobante', $logistica[0]->origen);

        $ultimo = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones(limite: 1));
        $this->assertCount(1, $ultimo);
        $porTotal = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones(orden: FiltroRenglones::ORDEN_TOTAL, dir: 'asc'));
        $this->assertSame('3500.00', $porTotal[0]->total);
    }

    public function test_fecha_efectiva_de_un_periodo_es_periodo_hasta(): void
    {
        $this->cargado($this->diego(), ['origen' => 'monto_periodo', 'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15',
            'renglones' => [$this->renglon('Horas liquidadas 1 al 15-oct', '1', '1300000', $this->nave, 'horas_liquidadas')]]);

        $renglon = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones)[0];
        $this->assertSame('2026-10-15', $renglon->fecha);
        $this->assertSame('2026-10-01', $renglon->periodoDesde);
        $this->assertSame('2026-10-15', $renglon->periodoHasta);
        $this->assertSame('monto_periodo', $renglon->origen);
        $this->assertSame('horas_liquidadas', $renglon->rubroClave);
        $this->assertSame('1.000', $renglon->cantidad);
    }

    public function test_renglones_con_material_incluyen_devoluciones_negativas(): void
    {
        $cemento = $this->material($this->nave, 'Cemento x 50 kg');
        $diego = $this->diego();
        $tecnobahia = $this->proveedor('Tecnobahía Ejemplo');
        $this->cargado($diego, ['origen' => 'retiro_remito', 'numero' => '1-456', 'fecha' => '2026-10-14', 'proveedor_id' => $tecnobahia->id,
            'renglones' => [$this->renglon('Cemento', '10', '9800', $this->nave, 'materiales', ['material_id' => $cemento->id])]]);
        $this->cargado($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-17',
            'renglones' => [$this->renglon('Cemento (sobrante)', '-4', '9800', $this->nave, 'materiales', ['material_id' => $cemento->id])]]);
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));

        $conMaterial = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones(soloConMaterial: true));
        $this->assertSame(['-4.000', '10.000'], array_map(static fn (RenglonImputado $r): string => $r->cantidad, $conMaterial));
        $this->assertSame('-39200.00', $conMaterial[0]->total);
        $this->assertSame($cemento->id, $conMaterial[1]->materialId);
        $this->assertSame('0001-00000456', $conMaterial[1]->numero);
    }

    public function test_contar_renglones_por_material_incluye_anulados(): void
    {
        $cemento = $this->material($this->nave, 'Cemento x 50 kg');
        $arena = $this->material($this->nave, 'Arena gruesa', 'm³');
        $diego = $this->diego();
        $datos = $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa);
        $datos['renglones'][0]['material_id'] = $cemento->id;
        $factura = $this->cargado($diego, $datos);
        $this->actingAs($diego)->postJson("/api/gastos/{$factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->assertSame([$cemento->id => 1], $this->gastado->contarRenglonesPorMaterial([$cemento->id, $arena->id]));
        $this->assertSame([], $this->gastado->contarRenglonesPorMaterial([]));
    }

    public function test_renglones_de_obra_sin_n_mas_1(): void
    {
        $diego = $this->diego();

        foreach (['1-1', '1-2', '1-3'] as $numero) {
            $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => $numero]));
        }

        DB::enableQueryLog();
        $renglones = $this->gastado->renglonesDeObra($this->nave->id, new FiltroRenglones);

        $this->assertCount(6, $renglones);
        $this->assertSame('Corralón Centro', $renglones[0]->proveedorNombre);
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_eventos_traen_todas_las_obras_afectadas(): void
    {
        Event::fake([GastoImputado::class, GastoCorregido::class]);
        $diego = $this->diego();
        $factura = $this->cargado($diego, ['origen' => 'factura', 'numero' => '1-1', 'fecha' => '2026-10-17', 'proveedor_id' => $this->proveedor()->id,
            'renglones' => [$this->renglon('Guantes', '24', '3150', $this->losa, 'insumos'), $this->renglon('Flete', '1', '45000', null, 'logistica')]]);

        Event::assertDispatched(GastoImputado::class, fn (GastoImputado $e): bool => $e->obraIds === [$this->losa->id]);

        $renglonId = $factura->renglones->first()?->id;
        $this->actingAs($diego)->patchJson("/api/gastos/{$factura->id}", ['renglones' => [
            $this->renglon('Guantes', '24', '3150', $this->nave, 'insumos', ['id' => $renglonId]),
        ]])->assertOk();

        Event::assertDispatched(GastoCorregido::class, fn (GastoCorregido $e): bool => $e->obraIds === [$this->losa->id, $this->nave->id]);
    }

    public function test_renglon_con_obra_siempre_tiene_rubro_en_la_base(): void
    {
        $this->expectException(QueryException::class);
        $factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        DB::table('gasto_renglones')->where('gasto_id', $factura->id)->where('obra_id', $this->nave->id)->update(['rubro_id' => null]);
    }

    /**
     * @param  array<int, array<string, string>>  $totales
     * @return array<int, array<string, string>>
     */
    private function ordenado(array $totales): array
    {
        ksort($totales);

        return $totales;
    }
}
