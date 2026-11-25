<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Gastos\Domain\Events\GastoAnulado;
use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-46 · F-14 · HU-072 · HU-073 · W-34: detalle, corregir con registro y anular (RN-GAS-36…42). */
final class CorregirYAnularTest extends GastosTestCase
{
    private Obra $nave;

    private Obra $losa;

    private User $diego;

    private Gasto $factura;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nave = $this->obra('Piso industrial nave 3');
        $this->losa = $this->obra('Reparación de losa tanque B');
        $this->diego = $this->diego();
        $this->factura = $this->cargado($this->diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa));
    }

    /**
     * Los renglones guardados como los manda el formulario de edición (con su `id`).
     *
     * @return list<array<string, mixed>>
     */
    private function renglonesActuales(): array
    {
        $detalle = $this->actingAs($this->diego)->getJson("/api/gastos/{$this->factura->id}")->json('data.renglones');
        $this->assertIsArray($detalle);

        return array_values(array_map(static fn (array $r): array => [
            'id' => $r['id'], 'concepto' => $r['concepto'], 'cantidad' => $r['cantidad'], 'precio_unitario' => $r['precio_unitario'],
            'obra_id' => $r['obra']['id'] ?? null, 'rubro' => $r['rubro']['clave'] ?? null, 'material_id' => $r['material']['id'] ?? null, 'detalle' => $r['detalle'],
        ], $detalle));
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return TestResponse<JsonResponse>
     */
    private function corregir(array $datos, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->diego)->patchJson("/api/gastos/{$this->factura->id}", $datos);
    }

    /**
     * @return list<string>
     */
    private function historial(): array
    {
        $lineas = $this->actingAs($this->diego)->getJson("/api/gastos/{$this->factura->id}/historial")->assertOk()->json('data');
        $this->assertIsArray($lineas);

        return array_column($lineas, 'que');
    }

    public function test_detalle_muestra_quien_cargo_el_reparto_y_la_version_en_hora_de_argentina(): void
    {
        $this->actingAs($this->marta())->getJson("/api/gastos/{$this->factura->id}")->assertOk()
            ->assertJsonPath('data.cargado_at', '2026-10-19T10:00:00-03:00')
            ->assertJsonPath('data.updated_at', '2026-10-19T10:00:00-03:00')
            ->assertJsonPath('data.updated_by', null)
            ->assertJsonPath('data.proveedor.nombre', 'Corralón Centro')
            ->assertJsonPath('data.renglones_ocultos', 0)
            ->assertJsonPath('data.anulacion', null)
            ->assertJsonPath('data.puede', ['editar' => false, 'anular' => false]);
        $this->assertSame(['Diego cargó la factura (5 renglones)'], $this->historial());
    }

    public function test_corregir_registra_valor_anterior_y_nuevo(): void
    {
        $renglones = $this->renglonesActuales();
        $renglones[3]['rubro'] = 'materiales';
        $renglones[0]['precio_unitario'] = '9500.00';
        $renglones[] = $this->renglon('Arena fina (m³)', '2', '40000', $this->nave, 'materiales');
        unset($renglones[2]);

        $this->corregir(['numero' => '3-12354', 'renglones' => array_values($renglones)])->assertOk()
            ->assertJsonPath('message', 'Guardamos los cambios.')
            ->assertJsonPath('data.numero', '0003-00012354')
            ->assertJsonPath('data.updated_by.nombre', 'Diego')
            ->assertJsonPath('data.total', '740600.00');

        $this->assertSame([
            'Diego cargó la factura (5 renglones)',
            'Diego cambió el número: 0003-00012345 → 0003-00012354',
            "Diego cambió el precio de 'Cemento x 50 kg': 9.800 → 9.500 (total 294.000 → 285.000)",
            "Diego cambió el rubro de 'Guantes de trabajo (par)': INSUMOS → MATERIALES",
            "Diego agregó el renglón 'Arena fina (m³)' · 2 × 40.000 · Piso industrial nave 3 · MATERIALES",
            "Diego quitó el renglón 'Disco diamantado 350 mm'",
        ], $this->historial());
    }

    public function test_corregir_sin_cambios_no_registra(): void
    {
        Event::fake([GastoCorregido::class]);
        $version = $this->actingAs($this->diego)->getJson("/api/gastos/{$this->factura->id}")->json('data.updated_at');

        $this->corregir(['numero' => '3-12345', 'renglones' => $this->renglonesActuales(), 'updated_at' => $version])->assertOk()
            ->assertJsonPath('data.updated_by', null);
        $this->assertSame(['creado'], $this->acciones($this->factura));
        Event::assertNotDispatched(GastoCorregido::class);
    }

    public function test_corregir_obra_de_un_renglon_avisa_las_dos_obras(): void
    {
        Event::fake([GastoCorregido::class]);
        $renglones = $this->renglonesActuales();
        $renglones[3]['obra_id'] = $this->nave->id;
        $renglones[4]['obra_id'] = $this->nave->id;

        $this->corregir(['renglones' => $renglones])->assertOk();

        Event::assertDispatched(GastoCorregido::class, function (GastoCorregido $e): bool {
            $obras = $e->obraIds;
            sort($obras);

            return $e->gastoId === $this->factura->id && $obras === [$this->nave->id, $this->losa->id];
        });
        $this->assertContains("Diego cambió la obra de 'Flete': (sin obra) → Piso industrial nave 3", $this->historial());
        $this->assertSame(
            ['materiales' => '549000.00', 'insumos' => '75600.00', 'logistica' => '45000.00'],
            $this->app->make(GastadoDeObras::class)->totalesPorRubro([$this->nave->id])[$this->nave->id],
        );
    }

    public function test_corregir_la_obra_de_un_renglon_con_material_de_otra_obra_es_rechazado(): void
    {
        $cemento = $this->material($this->nave, 'Cemento x 50 kg');
        $renglones = $this->renglonesActuales();
        $renglones[0]['material_id'] = $cemento->id;
        $this->corregir(['renglones' => $renglones])->assertOk();

        $renglones[0]['obra_id'] = $this->losa->id;
        $this->corregir(['renglones' => $renglones])->assertUnprocessable()->assertJsonPath('errors', $this->error(
            'renglones.0.material_id',
            'Renglón 1: el material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra.',
        ));
    }

    public function test_no_se_cambia_tipo_ni_origen(): void
    {
        $this->corregir(['tipo_documento' => 'sin_documento', 'origen' => 'combustible', 'proveedor_id' => $this->generico()->id])
            ->assertUnprocessable()->assertJsonPath('errors.proveedor_id.0', 'El proveedor genérico es solo para gastos sin documento.');
        $this->corregir(['tipo_documento' => 'sin_documento', 'origen' => 'combustible'])->assertOk()
            ->assertJsonPath('data.tipo_documento', 'factura')->assertJsonPath('data.origen', 'factura');
    }

    public function test_corregir_valida_la_factura_repetida_sin_contarse_a_si_misma(): void
    {
        $otra = $this->cargado($this->diego, $this->facturaDeW10($this->proveedor(), $this->nave, $this->losa, ['numero' => '3-999']));

        $this->corregir(['numero' => '0003-00012345'])->assertOk();
        $this->actingAs($this->diego)->patchJson("/api/gastos/{$otra->id}", ['numero' => '3-12345'])->assertUnprocessable()
            ->assertJsonPath('errors.numero.0', 'Esa factura ya está cargada: 0003-00012345 de Corralón Centro (Diego, 19-oct).');
    }

    public function test_corregir_con_version_vieja_devuelve_409(): void
    {
        $version = $this->actingAs($this->diego)->getJson("/api/gastos/{$this->factura->id}")->json('data.updated_at');
        $this->travel(5)->seconds();
        $this->corregir(['numero' => '3-11111', 'updated_at' => $version])->assertOk();

        $this->corregir(['numero' => '3-22222', 'updated_at' => $version], $this->carla())->assertStatus(409)
            ->assertJsonPath('message', 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.');
        $this->assertSame('0003-00011111', $this->factura->refresh()->numero);
    }

    public function test_cualquiera_con_gastos_editar_corrige(): void
    {
        $this->corregir(['fecha' => '2026-10-16'], $this->carla())->assertOk()
            ->assertJsonPath('data.renglones.0.fecha', '2026-10-16')
            ->assertJsonPath('data.updated_by.nombre', 'Carla');
        $this->corregir(['fecha' => '2026-10-15'], $this->marta())->assertForbidden();
    }

    public function test_anular_exige_motivo(): void
    {
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => ' '])->assertUnprocessable()
            ->assertJsonPath('errors.motivo.0', 'Contá por qué se anula.');
    }

    public function test_anulado_deja_de_sumar(): void
    {
        Event::fake([GastoAnulado::class]);

        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk()
            ->assertJsonPath('message', 'Anulamos la factura.')
            ->assertJsonPath('data.anulado', true)
            ->assertJsonPath('data.anulacion.motivo', 'Cargada dos veces')
            ->assertJsonPath('data.anulacion.anulado_por.nombre', 'Diego')
            ->assertJsonPath('data.puede', ['editar' => false, 'anular' => false]);

        $this->assertSame([$this->nave->id => [], $this->losa->id => []], $this->app->make(GastadoDeObras::class)->totalesPorRubro([$this->nave->id, $this->losa->id]));
        $this->assertContains("Diego anuló la factura: 'Cargada dos veces'", $this->historial());
        Event::assertDispatched(GastoAnulado::class, fn (GastoAnulado $e): bool => $e->obraIds === [$this->nave->id, $this->losa->id]);
    }

    public function test_anulado_no_se_corrige_ni_se_vuelve_a_anular(): void
    {
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->corregir(['numero' => '3-1'])->assertStatus(409)
            ->assertJsonPath('message', 'Está anulada: no se corrige. Si hace falta, cargala de nuevo.')
            ->assertJsonPath('data.anulado_at', '2026-10-19T10:00:00-03:00');
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Otra vez'])->assertStatus(409);
    }

    public function test_anular_con_la_misma_idempotency_key_no_anula_dos_veces(): void
    {
        $clave = ['Idempotency-Key' => '7c9e6679-7425-40de-944b-e07fc1f90ae7'];
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Cargada dos veces'], $clave)->assertOk();
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Cargada dos veces'], $clave)->assertOk()
            ->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame(['creado', 'anulado'], $this->acciones($this->factura));
    }

    public function test_no_hay_ruta_para_borrar_gastos(): void
    {
        $this->assertFalse(collect(Route::getRoutes()->getRoutes())->contains(
            static fn ($ruta): bool => str_starts_with($ruta->uri(), 'api/gastos') && in_array('DELETE', $ruta->methods(), true),
        ));
        $this->actingAs($this->diego)->deleteJson("/api/gastos/{$this->factura->id}")->assertStatus(405);
    }

    public function test_historial_informa_adjuntos_por_nombre_sin_url(): void
    {
        $link = $this->link($this->diego, 'comprobante_gasto', 'https://drive.example/privado/abc123/factura.pdf');
        $this->corregir(['adjuntos' => [$link]])->assertOk()->assertJsonCount(1, 'data.adjuntos');

        $historial = $this->historial();
        $this->assertContains('Diego agregó el archivo factura.pdf', $historial);
        $this->assertStringNotContainsString('drive.example', implode(' ', $historial));

        $this->corregir(['adjuntos' => []])->assertOk()->assertJsonCount(0, 'data.adjuntos');
        $this->assertContains('Diego quitó el archivo factura.pdf', $this->historial());
    }

    public function test_detalle_e_historial_de_un_gasto_inexistente_es_404(): void
    {
        $this->actingAs($this->diego)->getJson('/api/gastos/999999')->assertNotFound()->assertJsonPath('message', 'No encontramos este gasto.');
        $this->actingAs($this->diego)->getJson('/api/gastos/999999/historial')->assertNotFound();
        $this->actingAs($this->sofia())->getJson("/api/gastos/{$this->factura->id}")->assertForbidden();
    }
}
