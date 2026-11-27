<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Gastos\Support\GastosTestCase;
use Tests\Feature\Gastos\Support\PartesDeHoras;

/**
 * DU-C02 "Privacidad (valor hora por persona)": el nombre y el valor hora de cada renglón del parte los ven solo
 * quienes tienen `gastos = editar`; los demás, el parte resumido por obra (gastos.md RN-GAS-57, rendicion.md
 * RN-REN-62, 63). Contra el `GastadoDeObras` real.
 */
final class PrivacidadDelParteTest extends GastosTestCase
{
    use PartesDeHoras;

    private User $diego;

    private User $laura;

    private Obra $nave;

    private Obra $losa;

    private Gasto $parte;

    private int $planilla;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diego = $this->diego();
        $this->laura = $this->laura();
        $this->nave = $this->obra('Piso industrial nave 3', $this->laura);
        $this->losa = $this->obra('Reparación de losa tanque B');
        $this->planilla = $this->planillaSubida();
        // Juan 88,5 h y Pablo 8 h en Nave 3; Juan 16 h en Losa tanque B.
        $this->parte = $this->cargado($this->diego, [...$this->parteDelCaso49($this->nave, $this->losa, $this->nave), 'adjuntos' => [$this->planilla]]);
    }

    public function test_con_gastos_editar_el_detalle_muestra_cada_persona_y_su_valor_hora(): void
    {
        $this->actingAs($this->diego)->getJson("/api/gastos/{$this->parte->id}")->assertOk()
            ->assertJsonCount(3, 'data.renglones')
            ->assertJsonPath('data.renglones.0.empleado', 'Juan Pérez')
            ->assertJsonPath('data.renglones.0.precio_unitario', '5200.00')
            ->assertJsonPath('data.renglones.0.resumido', false)
            ->assertJsonCount(1, 'data.adjuntos');
    }

    public function test_sin_gastos_editar_el_detalle_muestra_el_parte_resumido_por_obra(): void
    {
        $respuesta = $this->actingAs($this->marta())->getJson("/api/gastos/{$this->parte->id}")->assertOk()
            ->assertJsonPath('data.total', '581800.00')
            ->assertJsonPath('data.adjuntos', []);
        $this->assertSame([$this->resumido($this->nave, '96.500', '498600.00'), $this->resumido($this->losa, '16.000', '83200.00')], $this->vistos($respuesta->json('data.renglones')));

        $respuesta->assertDontSee('Juan Pérez')->assertDontSee('Pablo Gómez')->assertDontSee('5200.00')->assertDontSee('4800.00');
    }

    public function test_con_alcance_propias_ve_resumido_solo_lo_de_sus_obras(): void
    {
        $respuesta = $this->actingAs($this->laura)->getJson("/api/gastos/{$this->parte->id}")->assertOk()
            ->assertJsonPath('data.renglones_ocultos', 1)
            ->assertDontSee('Juan Pérez');
        $this->assertSame([$this->resumido($this->nave, '96.500', '498600.00')], $this->vistos($respuesta->json('data.renglones')));
    }

    public function test_la_planilla_del_parte_no_se_baja_sin_gastos_editar(): void
    {
        $this->actingAs($this->marta())->get("/api/adjuntos/{$this->planilla}")->assertNotFound();
        $this->actingAs($this->laura)->get("/api/adjuntos/{$this->planilla}")->assertNotFound();
        $this->actingAs($this->diego)->get("/api/adjuntos/{$this->planilla}")->assertOk();
    }

    public function test_sin_gastos_editar_el_historial_no_muestra_los_cambios_de_renglones_ni_la_planilla(): void
    {
        $renglones = $this->parte->renglones->map(static fn (GastoRenglon $r): array => [
            'id' => $r->id, 'empleado' => $r->empleado, 'obra_id' => $r->obra_id, 'cantidad' => $r->cantidad, 'precio_unitario' => $r->precio_unitario,
        ])->all();
        $renglones[0]['precio_unitario'] = '5500.00';
        $this->actingAs($this->diego)->patchJson("/api/gastos/{$this->parte->id}", ['renglones' => $renglones])->assertOk();

        $historial = $this->actingAs($this->marta())->getJson("/api/gastos/{$this->parte->id}/historial")->assertOk();
        $this->assertSame(['Diego cargó el parte de horas (3 renglones)'], array_column($historial->json('data'), 'que'));
        $this->assertCount(3, $this->actingAs($this->diego)->getJson("/api/gastos/{$this->parte->id}/historial")->json('data'));
    }

    public function test_rendicion_muestra_el_parte_por_persona_con_gastos_editar(): void
    {
        $detalle = $this->actingAs($this->diego)->getJson("/api/obras/{$this->nave->id}/rendicion/rubros/horas_liquidadas")->assertOk()
            ->assertJsonPath('data.gastado', '498600.00')
            ->assertJsonCount(2, 'data.renglones');

        $this->assertEqualsCanonicalizing(['Juan Pérez', 'Pablo Gómez'], array_column($detalle->json('data.renglones'), 'empleado'));
        $this->assertSame('Parte de horas 1 al 15-oct', $detalle->json('data.renglones.0.documento'));
        $this->assertNotNull($detalle->json('data.renglones.0.precio_unitario'));

        $ultimos = $this->actingAs($this->diego)->getJson("/api/obras/{$this->nave->id}/rendicion")->assertOk()->json('data.ultimos_gastos');
        $this->assertCount(2, $ultimos);
        $this->assertEqualsCanonicalizing(['Juan Pérez', 'Pablo Gómez'], array_column($ultimos, 'empleado'));
    }

    public function test_rendicion_muestra_el_parte_resumido_sin_gastos_editar(): void
    {
        $detalle = $this->actingAs($this->laura)->getJson("/api/obras/{$this->nave->id}/rendicion/rubros/horas_liquidadas")->assertOk()
            ->assertJsonPath('data.gastado', '498600.00')
            ->assertJsonPath('data.cantidad_renglones', 1)
            ->assertJsonPath('data.renglones.0.concepto', 'Horas 1 al 15-oct')
            ->assertJsonPath('data.renglones.0.cantidad', '96.500')
            ->assertJsonPath('data.renglones.0.precio_unitario', null)
            ->assertJsonPath('data.renglones.0.total', '498600.00')
            ->assertJsonPath('data.renglones.0.empleado', null)
            ->assertJsonPath('data.renglones.0.resumido', true);
        $detalle->assertDontSee('Juan Pérez')->assertDontSee('Pablo Gómez');

        $rendicion = $this->actingAs($this->laura)->getJson("/api/obras/{$this->nave->id}/rendicion")->assertOk()
            ->assertJsonCount(1, 'data.ultimos_gastos')
            ->assertJsonPath('data.ultimos_gastos.0.total', '498600.00')
            ->assertJsonPath('data.ultimos_gastos.0.empleado', null);
        $rendicion->assertDontSee('Juan Pérez');

        $this->actingAs($this->laura)->getJson("/api/obras/{$this->nave->id}/rendicion/por-proveedor")->assertOk()->assertDontSee('Juan Pérez');
    }

    public function test_el_parte_suma_en_horas_liquidadas_de_cada_obra(): void
    {
        $rubros = $this->actingAs($this->diego)->getJson("/api/obras/{$this->losa->id}/rendicion")->assertOk()->json('data.rubros');

        $this->assertSame('83200.00', $this->conValor($rubros, 'clave', 'horas_liquidadas')['gastado']);
    }

    private function planillaSubida(): int
    {
        $id = $this->actingAs($this->diego)->post('/api/adjuntos', [
            'contexto' => 'comprobante_gasto',
            'archivo' => UploadedFile::fake()->createWithContent('horas-octubre.pdf', '%PDF-1.4
%prueba
'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function resumido(Obra $obra, string $horas, string $total): array
    {
        return [
            'id' => null, 'fecha' => '2026-10-15', 'concepto' => 'Horas 1 al 15-oct', 'cantidad' => $horas, 'precio_unitario' => null,
            'total' => $total, 'obra' => $obra->id, 'rubro' => 'horas_liquidadas', 'material' => null, 'empleado' => null, 'resumido' => true,
        ];
    }

    /**
     * @param  mixed  $renglones  `data.renglones` de W-34
     * @return list<array<string, mixed>>
     */
    private function vistos(mixed $renglones): array
    {
        $this->assertIsArray($renglones);

        return array_values(array_map(static fn (array $r): array => [
            'id' => $r['id'], 'fecha' => $r['fecha'], 'concepto' => $r['concepto'], 'cantidad' => $r['cantidad'], 'precio_unitario' => $r['precio_unitario'],
            'total' => $r['total'], 'obra' => $r['obra']['id'] ?? null, 'rubro' => $r['rubro']['clave'] ?? null, 'material' => $r['material'],
            'empleado' => $r['empleado'], 'resumido' => $r['resumido'],
        ], $renglones));
    }

    /**
     * La fila de una lista JSON que tiene ese valor en esa clave.
     *
     * @return array<string, mixed>
     */
    private function conValor(mixed $lista, string $clave, mixed $valor): array
    {
        $this->assertIsArray($lista);

        foreach ($lista as $fila) {
            if (is_array($fila) && ($fila[$clave] ?? null) === $valor) {
                return $fila;
            }
        }

        $this->fail("No hay una fila con {$clave}.");
    }
}
