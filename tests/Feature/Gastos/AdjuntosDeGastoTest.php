<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Shared\Adjuntos\Adjunto;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-43 · HU-064 · ADR-014: la factura como archivo o link (contexto `comprobante_gasto`, gastos.md §4.4). */
final class AdjuntosDeGastoTest extends GastosTestCase
{
    public function test_factura_acepta_archivo_o_link(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $link = $this->link($diego, 'comprobante_gasto');
        $archivo = $this->actingAs($diego)->post('/api/adjuntos', [
            'contexto' => 'comprobante_gasto',
            'archivo' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n%prueba\n"),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

        $gasto = $this->cargar($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave, ['adjuntos' => [$link, $archivo]]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.adjuntos')
            ->json('data.id');

        $this->actingAs($diego)->getJson("/api/gastos/{$gasto}/historial")->assertOk()
            ->assertJsonPath('data.1.que', 'Diego agregó el archivo factura-12345.pdf');
        $this->cargar($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave, ['numero' => '3-99']))->assertCreated();
    }

    public function test_adjunto_de_otro_contexto_no_se_asocia(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $dePedido = $this->link($diego, 'pedido');
        $ajeno = $this->link($this->carla(), 'comprobante_gasto');

        foreach ([$dePedido, $ajeno] as $adjunto) {
            $this->cargar($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave, ['adjuntos' => [$adjunto]]))
                ->assertUnprocessable()->assertJsonPath('errors.adjuntos.0', 'Ese adjunto no se puede usar acá.');
        }
    }

    public function test_adjunto_de_gasto_fuera_de_alcance_devuelve_404(): void
    {
        $laura = $this->laura();
        $nave = $this->obra('Piso industrial nave 3', $laura);
        $losa = $this->obra('Reparación de losa tanque B');
        $diego = $this->diego();
        $suyo = $this->link($diego, 'comprobante_gasto');
        $ajeno = $this->link($diego, 'comprobante_gasto', 'https://drive.example/facturas/hotel.pdf');
        $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $nave, $losa, ['adjuntos' => [$suyo]]));
        $this->cargado($diego, $this->facturaDeW10($this->proveedor('Hotel Puerto Ejemplo'), $losa, $losa, ['adjuntos' => [$ajeno]]));

        // El archivo es el comprobante entero: lo baja aunque vea solo sus renglones (RN-GAS-48). Un link no se
        // "descarga" (404 para todos), así que se prueba con quitar: sin editar es 403 si lo ve y 404 si no.
        $this->actingAs($laura)->deleteJson("/api/adjuntos/{$suyo}")->assertForbidden();
        $this->actingAs($laura)->deleteJson("/api/adjuntos/{$ajeno}")->assertNotFound();
    }

    public function test_gasto_anulado_conserva_sus_adjuntos(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $link = $this->link($diego, 'comprobante_gasto');
        $gasto = $this->cargado($diego, $this->facturaDeW10($this->proveedor(), $nave, $nave, ['adjuntos' => [$link]]));

        $this->actingAs($diego)->postJson("/api/gastos/{$gasto->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk()
            ->assertJsonCount(1, 'data.adjuntos');
        $this->actingAs($diego)->deleteJson("/api/adjuntos/{$link}")->assertForbidden();
        $this->assertTrue(Adjunto::query()->whereKey($link)->exists());
    }
}
