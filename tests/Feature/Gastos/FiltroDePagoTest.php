<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** DU-C03 · W-33 y W-18: filtro Pago con el total de lo filtrado y la marca "(impaga)" (gastos.md RN-GAS-61, 62). */
final class FiltroDePagoTest extends GastosTestCase
{
    private User $diego;

    private Obra $nave;

    private Gasto $impaga;

    private Gasto $paga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diego = $this->diego();
        $this->nave = $this->obra('Piso industrial nave 3');
        $losa = $this->obra('Reparación de losa tanque B');
        $this->impaga = $this->cargado($this->diego, $this->facturaDeW10($this->proveedor(), $this->nave, $losa));
        $this->paga = $this->cargado($this->diego, [
            'tipo_documento' => 'factura', 'origen' => 'factura', 'numero' => '5-3310', 'fecha' => '2026-10-16',
            'proveedor_id' => $this->proveedor('Ferretería Norte')->id,
            'renglones' => [$this->renglon('Guantes de trabajo (par)', '10', '3150.00', $this->nave, 'insumos')],
        ]);
        $this->cargado($this->diego, [
            'tipo_documento' => 'sin_documento', 'origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura',
            'renglones' => [$this->renglon('Combustible camioneta', '1', '68000.00', $this->nave, 'logistica')],
        ]);
        $this->actingAs($this->diego)->postJson("/api/gastos/{$this->paga->id}/marcar-paga", ['fecha' => '2026-10-18'])->assertOk();
    }

    public function test_filtro_impagas_deja_solo_las_facturas_sin_pagar_y_suma_lo_que_se_debe(): void
    {
        $this->actingAs($this->diego)->getJson('/api/gastos?pago=impagas')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->impaga->id)
            ->assertJsonPath('data.0.pago.estado', 'impaga')
            ->assertJsonPath('meta.total_filtrado', '905600.00')
            ->assertJsonPath('meta.comprobantes', 1);
    }

    public function test_filtro_pagas_y_se_suma_a_los_demas_filtros(): void
    {
        $this->actingAs($this->diego)->getJson('/api/gastos?pago=pagas')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pago', ['estado' => 'paga', 'pagado_at' => '2026-10-18', 'pagado_por' => ['id' => $this->diego->id, 'nombre' => 'Diego']])
            ->assertJsonPath('meta.total_filtrado', '31500.00');
        $this->actingAs($this->diego)->getJson('/api/gastos?pago=impagas&proveedor_id='.$this->proveedor('Ferretería Norte')->id)->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total_filtrado', '0.00');
    }

    public function test_sin_filtro_cada_fila_trae_su_estado_y_lo_que_no_es_factura_viene_en_null(): void
    {
        $filas = $this->actingAs($this->diego)->getJson('/api/gastos')->assertOk()->assertJsonCount(3, 'data')->json('data');
        $sinComprobante = $this->conValor($filas, 'origen', 'sin_comprobante');

        $this->assertArrayHasKey('pago', $sinComprobante);
        $this->assertNull($sinComprobante['pago']);
        $this->assertSame(['estado' => 'impaga', 'pagado_at' => null, 'pagado_por' => null], $this->conValor($filas, 'id', $this->impaga->id)['pago']);
    }

    public function test_sin_pagos_ver_el_filtro_da_403_y_las_filas_no_traen_el_estado(): void
    {
        $this->celda('Compras', 'pagos', 'sin_acceso');
        $carla = $this->carla();

        $this->actingAs($carla)->getJson('/api/gastos?pago=impagas')->assertForbidden()
            ->assertJsonPath('message', 'No tenés acceso al estado de pago de las facturas.');
        $this->actingAs($carla)->getJson('/api/gastos')->assertOk()->assertJsonMissingPath('data.0.pago');
    }

    public function test_filtro_pago_con_un_valor_que_no_es_de_la_lista_da_422(): void
    {
        $this->actingAs($this->diego)->getJson('/api/gastos?pago=todas')->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('pago', 'El valor no es válido.'));
    }

    public function test_buscador_marca_las_facturas_impagas(): void
    {
        $impaga = $this->resultado($this->diego, '12345');
        $paga = $this->resultado($this->diego, '3310');

        $this->assertTrue($impaga['impaga'] ?? null);
        $this->assertFalse($paga['impaga'] ?? null);
        $this->assertArrayNotHasKey('impaga', $this->resultado($this->persona('Jorge', 'Gerencia de Operaciones'), '12345'));
    }

    /**
     * @return array<string, mixed>
     */
    private function resultado(User $user, string $numero): array
    {
        $grupo = $this->conValor($this->actingAs($user)->getJson('/api/buscar?q='.$numero)->assertOk()->json('data.grupos'), 'tipo', 'gastos');
        $resultados = $grupo['resultados'] ?? null;
        $resultado = is_array($resultados) ? ($resultados[0] ?? null) : null;
        $this->assertIsArray($resultado);

        return $resultado;
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
