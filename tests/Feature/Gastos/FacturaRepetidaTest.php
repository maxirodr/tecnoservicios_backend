<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-39 · HU-065 · criterio de aceptación global n.º 2: una factura no se carga dos veces (RN-GAS-07, 08). */
final class FacturaRepetidaTest extends GastosTestCase
{
    public function test_misma_factura_del_mismo_proveedor_es_rechazada(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $corralon = $this->proveedor();
        $primera = $this->cargado($this->diego(), $this->facturaDeW10($corralon, $nave, $nave));

        $this->cargar($this->carla(), $this->facturaDeW10($corralon, $nave, $nave, ['numero' => '0003 00012345']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 19-oct).')
            ->assertJsonPath('errors.numero.0', 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 19-oct).')
            ->assertJsonPath('errors.existente.id', $primera->id);
        $this->assertSame(1, Gasto::query()->count());
    }

    public function test_mismo_numero_de_otro_proveedor_se_acepta(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave));

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor('Ferretería Norte'), $nave, $nave))->assertCreated();
    }

    public function test_factura_anulada_se_puede_volver_a_cargar(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $primera = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave));
        $this->actingAs($diego)->postJson("/api/gastos/{$primera->id}/anular", ['motivo' => 'Cargada con el proveedor equivocado'])->assertOk();

        $this->cargar($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave))->assertCreated();
        $this->assertSame(2, Gasto::query()->count());
    }

    public function test_dos_cargas_simultaneas_de_la_misma_factura_dejan_una(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $primera = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave));

        // La segunda carga que pasó el control antes de que la primera confirmara choca con el índice único.
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('gastos')->insert([
            'tipo_documento' => 'factura', 'origen' => 'factura', 'numero' => $primera->numero, 'fecha' => '2026-10-17',
            'proveedor_id' => $primera->proveedor_id, 'cargado_por' => $primera->cargado_por,
        ]);
    }

    public function test_aviso_de_factura_existente(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $corralon = $this->proveedor();
        $diego = $this->diego();
        $primera = $this->cargado($diego, $this->facturaDeW10($corralon, $nave, $nave));

        $this->actingAs($diego)->getJson("/api/gastos/factura-existente?proveedor_id={$corralon->id}&numero=3-12345")
            ->assertOk()
            ->assertJsonPath('data.existe', true)
            ->assertJsonPath('data.gasto.id', $primera->id)
            ->assertJsonPath('data.gasto.cargado_por.nombre', 'Diego');
        $this->actingAs($diego)->getJson("/api/gastos/factura-existente?proveedor_id={$corralon->id}&numero=3-12345&excluir_id={$primera->id}")
            ->assertOk()->assertJsonPath('data', ['existe' => false]);
        $this->actingAs($this->marta())->getJson("/api/gastos/factura-existente?proveedor_id={$corralon->id}&numero=3-12345")->assertForbidden();
    }

    public function test_aviso_de_factura_fuera_de_alcance_no_muestra_quien_ni_cual_es(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $laura = $this->laura();
        $nave = $this->obra('Piso industrial nave 3', $laura);
        $losa = $this->obra('Reparación de losa tanque B');
        $corralon = $this->proveedor();
        $this->cargado($this->diego(), $this->facturaDeW10($corralon, $losa, $losa));
        $aviso = 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro.';

        $this->actingAs($laura)->getJson("/api/gastos/factura-existente?proveedor_id={$corralon->id}&numero=3-12345")
            ->assertOk()->assertJsonPath('data', ['existe' => true, 'mensaje' => $aviso]);
        $this->cargar($laura, $this->facturaDeW10($corralon, $nave, $nave, [
            'renglones' => [$this->renglon('Cemento x 50 kg', '30', '9800.00', $nave, 'materiales')],
        ]))->assertUnprocessable()
            ->assertJsonPath('message', $aviso)
            ->assertJsonPath('errors', ['numero' => [$aviso]]);
    }

    public function test_aviso_de_factura_en_su_alcance_muestra_la_factura(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $laura = $this->laura();
        $nave = $this->obra('Piso industrial nave 3', $laura);
        $corralon = $this->proveedor();
        $primera = $this->cargado($this->diego(), $this->facturaDeW10($corralon, $nave, $this->obra('Reparación de losa tanque B')));

        $this->actingAs($laura)->getJson("/api/gastos/factura-existente?proveedor_id={$corralon->id}&numero=3-12345")
            ->assertOk()->assertJsonPath('data.gasto.id', $primera->id)
            ->assertJsonPath('data.mensaje', 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 19-oct).');
    }

    public function test_si_otra_carga_confirma_la_misma_factura_en_el_medio_la_segunda_recibe_el_422(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $corralon = $this->proveedor();
        $diego = $this->diego();
        // La otra carga entra entre el control y el INSERT de esta: el índice único choca y la acción responde 422.
        Gasto::creating(static function () use ($corralon, $diego): void {
            if (DB::table('gastos')->where('numero', '0003-00012345')->doesntExist()) {
                DB::table('gastos')->insert([
                    'tipo_documento' => 'factura', 'origen' => 'factura', 'numero' => '0003-00012345', 'fecha' => '2026-10-17',
                    'proveedor_id' => $corralon->id, 'cargado_por' => $diego->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $this->cargar($this->carla(), $this->facturaDeW10($corralon, $nave, $nave))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 19-oct).')
            ->assertJsonPath('errors.existente.cargado_por.nombre', 'Diego');
    }
}
