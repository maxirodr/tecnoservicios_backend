<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use Illuminate\Database\QueryException;
use PDOException;
use Tests\Feature\Rendicion\Support\RendicionTestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** F-15 · F-17 · HU-081 (W-05, W-07): el "% del presupuesto gastado" sale de la rendición (RN-REN-42, RN-OBR-04). */
final class PorcentajeGastadoEnObrasTest extends RendicionTestCase
{
    public function test_avance_gasto_devuelve_porcentajes_en_una_consulta_y_nulo_sin_presupuesto(): void
    {
        $diego = $this->diego();
        $conPresupuesto = $this->obraDe($diego);
        $sinPresupuesto = $this->obraDe($diego);
        $this->porRubro($this->principal($conPresupuesto), ['materiales' => '1000.00']);
        $this->gastado->agregar($conPresupuesto->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '1200.00', 'materiales'));
        $this->gastado->agregar($sinPresupuesto->id, Renglon::de(2, 2, '2026-10-02', 'factura', 'Cemento', '500.00', 'materiales'));
        $avance = $this->app->make(AvanceGastoDeObra::class);

        $this->assertSame([$conPresupuesto->id => '120.00', $sinPresupuesto->id => null], $avance->porcentajesGastados([$conPresupuesto->id, $sinPresupuesto->id]));
        $this->assertSame(1, $this->gastado->consultasDeTotales);
        $this->assertSame([$conPresupuesto->id => true, $sinPresupuesto->id => false], $avance->pasadas([$conPresupuesto->id, $sinPresupuesto->id]));
    }

    public function test_el_listado_y_la_ficha_muestran_el_porcentaje_y_ordenan_en_ejecucion_por_el(): void
    {
        $diego = $this->diego();
        [$poco, $mucho] = [$this->obraDe($diego), $this->obraDe($diego)];
        $this->porRubro($this->principal($poco), ['materiales' => '1000.00']);
        $this->porRubro($this->principal($mucho), ['materiales' => '1000.00']);
        $this->gastado->agregar($poco->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '100.00', 'materiales'));
        $this->gastado->agregar($mucho->id, Renglon::de(2, 2, '2026-10-02', 'factura', 'Cemento', '1500.00', 'materiales'));

        $filas = $this->actingAs($diego)->getJson('/api/obras?estado=en_ejecucion')->assertOk()->json('data');

        $this->assertSame([[$mucho->id, '150.00', true], [$poco->id, '10.00', false]], array_map(static fn (array $f): array => [$f['id'], $f['porcentaje_gastado'], $f['pasado_de_presupuesto']], $filas));
        $this->getJson("/api/obras/{$mucho->id}")->assertOk()->assertJsonPath('data.porcentaje_gastado', '150.00');
    }

    public function test_porcentaje_gastado_se_oculta_sin_permiso_de_rendicion(): void
    {
        $laura = $this->laura();
        $suya = $this->obraDe($laura);
        $ajena = $this->obraDe($this->hernan());
        foreach ([$suya, $ajena] as $obra) {
            $this->porRubro($this->principal($obra), ['materiales' => '1000.00']);
        }

        $filas = array_column((array) $this->actingAs($laura)->getJson('/api/obras?estado=en_ejecucion')->assertOk()->json('data'), null, 'id');

        $this->assertSame('0.00', $filas[$suya->id]['porcentaje_gastado']);
        $this->assertNull($filas[$ajena->id]['porcentaje_gastado']);
    }

    public function test_si_la_lectura_de_gastos_falla_el_listado_no_se_cae(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $this->porRubro($this->principal($obra), ['materiales' => '1000.00']);
        $this->app->instance(GastadoDeObras::class, new class implements GastadoDeObras
        {
            public function totalesPorRubro(array $obraIds): array
            {
                throw new QueryException('mysql', 'select 1', [], new PDOException('Base de gastos caída (simulada).'));
            }

            public function renglonesDeObra(int $obraId, FiltroRenglones $filtro): array
            {
                return [];
            }

            public function contarRenglonesPorMaterial(array $materialIds): array
            {
                return [];
            }
        });

        $this->actingAs($diego)->getJson('/api/obras?estado=en_ejecucion')->assertOk()->assertJsonPath('data.0.porcentaje_gastado', null);
    }

    public function test_obra_con_presupuesto_en_cero_y_gasto_queda_pasada_sin_porcentaje(): void
    {
        $diego = $this->diego();
        $cero = $this->obraDe($diego);
        $sinPresupuesto = $this->obraDe($diego);
        $this->porRubro($this->principal($cero), []);
        $this->gastado->agregar($cero->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '500.00', 'materiales'));
        $this->gastado->agregar($sinPresupuesto->id, Renglon::de(2, 2, '2026-10-02', 'factura', 'Cemento', '500.00', 'materiales'));

        // N11: hay presupuesto cargado y N5 > N3 (0); N9 no se calcula (N3 = 0).
        $filas = array_column((array) $this->actingAs($diego)->getJson('/api/obras?estado=en_ejecucion')->assertOk()->json('data'), null, 'id');

        $this->assertSame([null, true], [$filas[$cero->id]['porcentaje_gastado'], $filas[$cero->id]['pasado_de_presupuesto']]);
        $this->assertSame([null, null], [$filas[$sinPresupuesto->id]['porcentaje_gastado'], $filas[$sinPresupuesto->id]['pasado_de_presupuesto']]);
        $this->getJson("/api/obras/{$cero->id}")->assertOk()->assertJsonPath('data.pasado_de_presupuesto', true);
    }

    public function test_el_listado_calcula_porcentaje_y_pasadas_con_una_sola_lectura(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $this->porRubro($this->principal($obra), ['materiales' => '1000.00']);

        $this->actingAs($diego)->getJson('/api/obras?estado=en_ejecucion')->assertOk();

        $this->assertSame(1, $this->gastado->consultasDeTotales, 'RN-REN-42: una consulta por agregado para toda la página.');
    }
}
