<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Events\GastoCorregido;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** DU-C03 · W-34: marcar una factura como paga o impaga (gastos.md RN-GAS-58…60, 62, 63). */
final class EstadoDePagoTest extends GastosTestCase
{
    private User $maria;

    private Obra $nave;

    private Gasto $factura;

    protected function setUp(): void
    {
        parent::setUp();

        // Gerencia de Administración: Pagos de facturas = Editar (configuración inicial, DU-C03).
        $this->maria = $this->persona('María', 'Gerencia de Administración');
        $this->nave = $this->obra('Piso industrial nave 3', $this->laura());
        $this->factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->obra('Reparación de losa tanque B')));
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>  $headers
     * @return TestResponse<JsonResponse>
     */
    private function marcar(string $accion, array $datos = [], ?User $user = null, ?Gasto $gasto = null, array $headers = []): TestResponse
    {
        $gasto ??= $this->factura;

        return $this->actingAs($user ?? $this->maria)->postJson("/api/gastos/{$gasto->id}/marcar-{$accion}", $datos, $headers);
    }

    /**
     * @return list<string>
     */
    private function historial(User $user): array
    {
        $lineas = $this->actingAs($user)->getJson("/api/gastos/{$this->factura->id}/historial")->assertOk()->json('data');
        $this->assertIsArray($lineas);

        return array_column($lineas, 'que');
    }

    public function test_cada_factura_arranca_impaga(): void
    {
        $this->actingAs($this->maria)->getJson("/api/gastos/{$this->factura->id}")->assertOk()
            ->assertJsonPath('data.pago', ['estado' => 'impaga', 'pagado_at' => null, 'pagado_por' => null])
            ->assertJsonPath('data.puede.marcar_pago', true);
    }

    public function test_marcar_paga_con_la_fecha_del_pago_y_despues_impaga(): void
    {
        $this->marcar('paga', ['fecha' => '2026-10-18'])->assertOk()
            ->assertJsonPath('message', 'Marcamos la factura como paga.')
            ->assertJsonPath('data.pago', ['estado' => 'paga', 'pagado_at' => '2026-10-18', 'pagado_por' => ['id' => $this->maria->id, 'nombre' => 'María']])
            ->assertJsonPath('data.updated_by.nombre', 'María');

        $this->marcar('impaga')->assertOk()
            ->assertJsonPath('message', 'Marcamos la factura como impaga.')
            ->assertJsonPath('data.pago.estado', 'impaga');

        $this->assertSame([
            'Diego cargó la factura (5 renglones)',
            'María marcó la factura como paga (18-10-2026)',
            'María marcó la factura como impaga',
        ], $this->historial($this->maria));
    }

    public function test_sin_fecha_se_marca_paga_hoy(): void
    {
        $this->marcar('paga')->assertOk()->assertJsonPath('data.pago.pagado_at', self::HOY);
    }

    public function test_marcar_de_nuevo_con_la_misma_fecha_no_registra_nada_y_con_otra_la_cambia(): void
    {
        $this->marcar('paga', ['fecha' => '2026-10-18'])->assertOk();
        $this->marcar('paga', ['fecha' => '2026-10-18'])->assertOk();
        $this->marcar('impaga')->assertOk();
        $this->marcar('impaga')->assertOk();
        $this->marcar('paga', ['fecha' => '2026-10-18'])->assertOk();
        $this->marcar('paga', ['fecha' => '2026-10-19'])->assertOk()->assertJsonPath('data.pago.pagado_at', '2026-10-19');

        $this->assertSame([
            'Diego cargó la factura (5 renglones)',
            'María marcó la factura como paga (18-10-2026)',
            'María marcó la factura como impaga',
            'María marcó la factura como paga (18-10-2026)',
            'María cambió la fecha del pago: 18-10-2026 → 19-10-2026',
        ], $this->historial($this->maria));
    }

    public function test_marcar_paga_no_toca_la_rendicion(): void
    {
        Event::fake([GastoCorregido::class]);

        $this->marcar('paga')->assertOk()->assertJsonPath('data.total', '905600.00');

        Event::assertNotDispatched(GastoCorregido::class);
    }

    public function test_marcar_pago_pide_pagos_editar(): void
    {
        $carla = $this->carla();

        // Compras: Pagos = Ver. Ingeniería y Seguridad e Higiene: sin acceso.
        $this->marcar('paga', [], $carla)->assertForbidden()->assertJsonPath('message', 'No tenés permiso para hacer esto.');
        $this->marcar('impaga', [], $this->laura())->assertForbidden();
        $this->marcar('paga', [], $this->sofia())->assertForbidden();
        $this->actingAs($carla)->getJson("/api/gastos/{$this->factura->id}")->assertOk()
            ->assertJsonPath('data.pago.estado', 'impaga')
            ->assertJsonPath('data.puede.marcar_pago', false);
        $this->assertNull($this->factura->fresh()?->pagado_at);
    }

    public function test_factura_fuera_de_alcance_o_inexistente_devuelve_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'pagos', 'editar');
        $hernan = $this->persona('Hernán', 'Ingeniería de proyectos');

        $this->marcar('paga', [], $hernan)->assertNotFound()->assertJsonPath('message', 'No encontramos este gasto.');
        $this->actingAs($this->maria)->postJson('/api/gastos/999999/marcar-paga')->assertNotFound();
        $this->assertNull($this->factura->fresh()?->pagado_at);
    }

    public function test_solo_las_facturas_se_marcan(): void
    {
        $sinComprobante = $this->cargado($this->diego(), [
            'tipo_documento' => 'sin_documento', 'origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura',
            'renglones' => [$this->renglon('Combustible camioneta', '1', '68000.00', $this->nave, 'logistica')],
        ]);

        $this->marcar('paga', [], null, $sinComprobante)->assertUnprocessable()
            ->assertJsonPath('message', 'Solo las facturas se marcan como pagas o impagas.');
        $this->actingAs($this->maria)->getJson("/api/gastos/{$sinComprobante->id}")->assertOk()
            ->assertJsonPath('data.pago', null)
            ->assertJsonPath('data.puede.marcar_pago', false);
    }

    public function test_una_factura_anulada_no_se_marca(): void
    {
        $this->actingAs($this->diego())->postJson("/api/gastos/{$this->factura->id}/anular", ['motivo' => 'Cargada dos veces'])->assertOk();

        $this->marcar('paga')->assertStatus(409)->assertJsonPath('message', 'Está anulada: no se marca como paga ni impaga.');
        $this->actingAs($this->maria)->getJson("/api/gastos/{$this->factura->id}")->assertJsonPath('data.puede.marcar_pago', false);
    }

    public function test_la_fecha_del_pago_no_puede_ser_posterior_a_hoy(): void
    {
        $this->marcar('paga', ['fecha' => '2026-10-20'])->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('fecha', 'La fecha no puede ser posterior a hoy.'));
        $this->marcar('paga', ['fecha' => '18/10/2026'])->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('fecha', 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.'));
    }

    public function test_marcar_con_version_vieja_devuelve_409(): void
    {
        $abierta = $this->actingAs($this->maria)->getJson("/api/gastos/{$this->factura->id}")->json('data.updated_at');
        Carbon::setTestNow(Carbon::now()->addMinute());
        $this->actingAs($this->diego())->patchJson("/api/gastos/{$this->factura->id}", ['numero' => '3-12346'])->assertOk();

        $this->marcar('paga', ['updated_at' => $abierta])->assertStatus(409)
            ->assertJsonPath('message', 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.');

        $nueva = $this->actingAs($this->maria)->getJson("/api/gastos/{$this->factura->id}")->json('data.updated_at');
        $this->marcar('paga', ['updated_at' => $nueva])->assertOk();
    }

    public function test_marcar_paga_acepta_idempotency_key(): void
    {
        $clave = ['Idempotency-Key' => '5b0f6a2e-6f1d-4c39-9f0b-2a7c3d9e1f40'];

        $primera = $this->marcar('paga', ['fecha' => '2026-10-18'], null, null, $clave)->assertOk();
        $segunda = $this->marcar('paga', ['fecha' => '2026-10-18'], null, null, $clave)->assertOk();

        $this->assertSame($primera->json(), $segunda->json());
        $this->assertSame(['creado', 'pagada'], $this->acciones($this->factura));
    }

    public function test_sin_pagos_ver_no_viaja_el_estado_ni_su_historial(): void
    {
        $this->marcar('paga')->assertOk();
        $laura = $this->persona('Laura B.', 'Ingeniería de proyectos');
        $this->nave->forceFill(['responsable_id' => $laura->id])->save();

        $this->actingAs($laura)->getJson("/api/gastos/{$this->factura->id}")->assertOk()
            ->assertJsonMissingPath('data.pago')
            ->assertJsonPath('data.puede.marcar_pago', false);
        $this->assertSame(['Diego cargó la factura (5 renglones)'], $this->historial($laura));
        $this->actingAs($this->marta())->getJson("/api/gastos/{$this->factura->id}")->assertOk()
            ->assertJsonPath('data.pago.estado', 'paga')
            ->assertJsonPath('data.puede.marcar_pago', false);
    }
}
