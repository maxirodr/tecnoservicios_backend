<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-61 · ADR-006 · DU-A04: "solo sus obras" en Gastos (RN-GAS-48, 49), IDOR y opciones de la carga. */
final class AlcanceYPermisosTest extends GastosTestCase
{
    private User $laura;

    private Obra $nave;

    private Obra $losa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laura = $this->laura();
        $this->nave = $this->obra('Piso industrial nave 3', $this->laura);
        $this->losa = $this->obra('Reparación de losa tanque B');
    }

    public function test_alcance_propias_ve_solo_sus_renglones(): void
    {
        $factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));

        $this->actingAs($this->laura)->getJson("/api/gastos/{$factura->id}")->assertOk()
            ->assertJsonCount(2, 'data.renglones')
            ->assertJsonPath('data.renglones_ocultos', 3)
            ->assertJsonPath('data.total', '549000.00')
            ->assertJsonPath('data.obras', [['id' => $this->nave->id, 'nombre_trabajo' => 'Piso industrial nave 3']])
            ->assertJsonPath('data.puede', ['editar' => false, 'anular' => false, 'marcar_pago' => false]);
    }

    public function test_alcance_propias_total_es_de_sus_renglones(): void
    {
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
        $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor('Hotel Puerto Ejemplo'), $this->losa, $this->losa, ['numero' => '5-3310']));

        $this->actingAs($this->laura)->getJson('/api/gastos')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.total', '549000.00')
            ->assertJsonPath('data.0.total_filtrado', '549000.00')
            ->assertJsonPath('meta.total_filtrado', '549000.00')
            ->assertJsonPath('meta.comprobantes', 1);
        $this->actingAs($this->laura)->getJson('/api/gastos?obra_id=sin_obra')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_gasto_sin_renglones_en_sus_obras_devuelve_404(): void
    {
        $hotel = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor('Hotel Puerto Ejemplo'), $this->losa, $this->losa, ['numero' => '5-3310']));

        $this->actingAs($this->laura)->getJson("/api/gastos/{$hotel->id}")->assertNotFound()->assertJsonPath('message', 'No encontramos este gasto.');
        $this->actingAs($this->laura)->getJson("/api/gastos/{$hotel->id}/historial")->assertNotFound();
    }

    public function test_alcance_propias_baja_el_archivo_de_la_factura(): void
    {
        $diego = $this->diego();
        $archivo = $this->actingAs($diego)->post('/api/adjuntos', [
            'contexto' => 'comprobante_gasto',
            'archivo' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n%prueba\n"),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['adjuntos' => [$archivo]]));

        $this->actingAs($this->laura)->get("/api/adjuntos/{$archivo}")->assertOk();
        $this->actingAs($this->sofia())->get("/api/adjuntos/{$archivo}")->assertNotFound();
    }

    public function test_editar_propias_no_imputa_a_otras_obras_ni_sin_obra(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $ajena = $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa);

        $this->cargar($this->laura, $ajena)->assertForbidden()->assertJsonPath('message', 'Con tu permiso solo podés imputar a tus obras.');
        $suya = $this->facturaDeW10($this->proveedor(), $this->nave, $this->nave);
        $this->cargar($this->laura, $suya)->assertForbidden();

        $suya['renglones'][4]['obra_id'] = $this->nave->id;
        $this->cargar($this->laura, $suya)->assertCreated();
    }

    public function test_editar_propias_no_corrige_facturas_con_renglones_ajenos(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');
        $factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));

        $this->actingAs($this->laura)->patchJson("/api/gastos/{$factura->id}", ['numero' => '3-1'])->assertForbidden()
            ->assertJsonPath('message', 'Esta factura tiene renglones de obras que no son tuyas: la corrige administración.');
        $this->actingAs($this->laura)->postJson("/api/gastos/{$factura->id}/anular", ['motivo' => 'x'])->assertForbidden();
    }

    public function test_laura_con_gastos_ver_no_carga(): void
    {
        $this->cargar($this->laura, $this->facturaDeW10($this->proveedor(), $this->nave, $this->nave))->assertForbidden();
    }

    public function test_opciones_de_la_carga(): void
    {
        $this->material($this->nave, 'Cemento x 50 kg');
        $diego = $this->diego();

        $this->actingAs($diego)->getJson('/api/gastos/opciones')->assertOk()
            ->assertJsonCount(13, 'data.rubros')
            ->assertJsonPath('data.rubros.12', ['clave' => 'horas_liquidadas', 'nombre' => 'HORAS LIQUIDADAS', 'se_imputa' => false, 'carga_por_periodo' => true])
            ->assertJsonPath('data.origenes_sin_documento', ['sin_comprobante', 'combustible', 'consumo_stock']);

        $obras = $this->actingAs($diego)->getJson('/api/gastos/obras-opciones?buscar=nave')->assertOk()->json('data');
        $this->assertSame([['id' => $this->nave->id, 'numero' => $obras[0]['numero'], 'nombre_trabajo' => 'Piso industrial nave 3', 'estado' => 'en_ejecucion', 'tiene_materiales' => true]], $obras);

        $this->actingAs($this->laura)->getJson('/api/gastos/opciones')->assertForbidden();
        $this->actingAs($this->sofia())->getJson('/api/gastos/obras-opciones')->assertForbidden();
    }

    public function test_opciones_de_obras_con_alcance_propias_solo_las_suyas(): void
    {
        $this->celda('Ingeniería de proyectos', 'gastos', 'editar', 'propias');

        $this->actingAs($this->laura)->getJson('/api/gastos/obras-opciones')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $this->nave->id);
    }
}
