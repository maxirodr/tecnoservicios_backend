<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * F-57 · HU-084 · W-14 "Gastado por obra" (RN-GAS-43) y F-03 · HU-085 · W-18 grupo Gastos del buscador
 * (RN-GAS-44, resuelta DU-G07).
 */
final class ProveedorYBuscadorTest extends GastosTestCase
{
    private Obra $nave;

    private Obra $losa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nave = $this->obra('Piso industrial nave 3');
        $this->losa = $this->obra('Reparación de losa tanque B');
    }

    public function test_gastado_por_obra_agrupa_por_obra_con_fila_sin_obra(): void
    {
        $corralon = $this->proveedor();
        $this->cargado($this->diego(), $this->facturaDeW10($corralon, $this->nave, $this->losa));

        $this->actingAs($this->marta())->getJson("/api/proveedores/{$corralon->id}/gastado-por-obra")->assertOk()
            ->assertJsonCount(3, 'data.filas')
            ->assertJsonPath('data.filas.0.obra.nombre_trabajo', 'Piso industrial nave 3')
            ->assertJsonPath('data.filas.0.renglones', 2)
            ->assertJsonPath('data.filas.0.importe', '549000.00')
            ->assertJsonPath('data.filas.1.importe', '311600.00')
            ->assertJsonPath('data.filas.2', ['obra' => null, 'renglones' => 1, 'importe' => '45000.00'])
            ->assertJsonPath('data.total', ['renglones' => 5, 'importe' => '905600.00']);
    }

    public function test_gastado_por_obra_excluye_anulados(): void
    {
        $corralon = $this->proveedor();
        $diego = $this->diego();
        $factura = $this->cargado($diego, $this->facturaDeW10($corralon, $this->nave, $this->losa));
        $this->actingAs($diego)->postJson("/api/gastos/{$factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->actingAs($this->marta())->getJson("/api/proveedores/{$corralon->id}/gastado-por-obra")->assertOk()
            ->assertJsonPath('data', ['filas' => [], 'total' => ['renglones' => 0, 'importe' => '0.00']]);
    }

    public function test_gastado_por_obra_resta_devoluciones(): void
    {
        $corralon = $this->proveedor();
        $diego = $this->diego();
        $this->cargado($diego, ['origen' => 'factura', 'numero' => '1-1', 'fecha' => '2026-10-10', 'proveedor_id' => $corralon->id,
            'renglones' => [$this->renglon('Cemento', '30', '9800', $this->nave, 'materiales')]]);
        $this->cargado($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-17', 'proveedor_id' => $corralon->id,
            'renglones' => [$this->renglon('Cemento (devuelto)', '-5', '9800', $this->nave, 'materiales')]]);

        $this->actingAs($this->marta())->getJson("/api/proveedores/{$corralon->id}/gastado-por-obra")->assertOk()
            ->assertJsonPath('data.filas.0.renglones', 2)
            ->assertJsonPath('data.filas.0.importe', '245000.00');
    }

    public function test_gastado_por_obra_con_alcance_propias_muestra_solo_sus_obras(): void
    {
        $laura = $this->laura();
        $suya = $this->obra('Ampliación de nave de ensayo', $laura);
        $corralon = $this->proveedor();
        $this->cargado($this->diego(), $this->facturaDeW10($corralon, $suya, $this->losa));
        $this->celda('Ingeniería de proyectos', 'proveedores', 'ver');

        $this->actingAs($laura)->getJson("/api/proveedores/{$corralon->id}/gastado-por-obra")->assertOk()
            ->assertJsonCount(1, 'data.filas')
            ->assertJsonPath('data.filas.0.obra.id', $suya->id)
            ->assertJsonPath('data.total.importe', '549000.00');
    }

    public function test_gastado_por_obra_en_una_consulta(): void
    {
        $corralon = $this->proveedor();
        $diego = $this->diego();

        foreach (['1-1', '1-2', '1-3'] as $numero) {
            $this->cargado($diego, $this->facturaDeW10($corralon, $this->nave, $this->losa, ['numero' => $numero]));
        }

        $marta = $this->marta();
        DB::enableQueryLog();
        $this->actingAs($marta)->getJson("/api/proveedores/{$corralon->id}/gastado-por-obra")->assertOk()->assertJsonPath('data.total.renglones', 15);

        $this->assertCount(1, array_filter(DB::getQueryLog(), static fn (array $q): bool => str_contains($q['query'], 'gasto_renglones')));
    }

    public function test_buscador_encuentra_por_numero_sin_ceros_ni_guiones(): void
    {
        $factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));

        foreach (['12345', '3-12345', '0003 00012345'] as $texto) {
            $grupo = $this->grupoGastos($this->marta(), $texto);
            $this->assertSame(1, $grupo['cantidad'] ?? null, $texto);
            $this->assertSame([
                'id' => $factura->id, 'documento' => 'Factura 0003-00012345', 'proveedor' => ['id' => $factura->proveedor_id, 'nombre' => 'Corralón Centro', 'es_generico' => false],
                'fecha' => '2026-10-17', 'total' => '905600.00', 'anulado' => false, 'impaga' => true,
            ], $grupo['resultados'][0] ?? null);
        }

        $this->assertSame(0, $this->grupoGastos($this->marta(), '12')['cantidad'] ?? null);
        $this->assertSame(0, $this->grupoGastos($this->marta(), 'Cemento')['cantidad'] ?? null);
    }

    public function test_buscador_encuentra_remitos(): void
    {
        $cemento = $this->material($this->nave, 'Cemento x 50 kg');
        $this->cargado($this->diego(), ['origen' => 'retiro_remito', 'numero' => '1-456', 'fecha' => '2026-10-14', 'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id,
            'renglones' => [$this->renglon('Cemento', '10', '9800', $this->nave, 'materiales', ['material_id' => $cemento->id])]]);

        $grupo = $this->grupoGastos($this->marta(), '456');
        $this->assertSame('Retiro · remito 0001-00000456', $grupo['resultados'][0]['documento'] ?? null);
    }

    public function test_buscador_de_gastos_respeta_el_alcance(): void
    {
        $laura = $this->laura();
        $suya = $this->obra('Ampliación de nave de ensayo', $laura);
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $suya, $this->losa));
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor('Hotel Puerto Ejemplo'), $this->losa, $this->losa, ['numero' => '5-12345']));

        $grupo = $this->grupoGastos($laura, '12345');
        $this->assertSame(1, $grupo['cantidad'] ?? null);
        $this->assertSame('549000.00', $grupo['resultados'][0]['total'] ?? null);
    }

    public function test_grupo_gastos_no_viaja_sin_permiso(): void
    {
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));

        $grupos = $this->actingAs($this->sofia())->getJson('/api/buscar?q=12345')->assertOk()->json('data.grupos');
        $this->assertIsArray($grupos);
        $this->assertNotContains('gastos', array_column($grupos, 'tipo'));
    }

    public function test_buscador_marca_los_anulados(): void
    {
        $diego = $this->diego();
        $factura = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        $this->actingAs($diego)->postJson("/api/gastos/{$factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->assertTrue($this->grupoGastos($this->marta(), '12345')['resultados'][0]['anulado'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function grupoGastos(User $user, string $texto): array
    {
        $grupos = $this->actingAs($user)->getJson('/api/buscar?q='.urlencode($texto))->assertOk()->json('data.grupos');
        $this->assertIsArray($grupos);

        foreach ($grupos as $grupo) {
            if (is_array($grupo) && ($grupo['tipo'] ?? null) === 'gastos') {
                return $grupo;
            }
        }

        $this->fail('El grupo Gastos no vino.');
    }
}
