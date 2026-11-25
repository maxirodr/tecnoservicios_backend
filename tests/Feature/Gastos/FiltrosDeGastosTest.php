<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * Resuelta DU-G12 (cierra ALT-1 y ALT-2 del QC S3): las opciones de los filtros Obra y Proveedor de W-33
 * responden a quien tiene `gastos` ≥ ver, con su alcance y búsqueda por texto (máx. 20 por búsqueda).
 */
final class FiltrosDeGastosTest extends GastosTestCase
{
    public function test_quien_solo_ve_gastos_trae_las_opciones_de_los_filtros(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $this->cargado($this->diego(), $this->factura($this->proveedor(), $nave, '3-1'));

        $marta = $this->marta();

        $obras = $this->actingAs($marta)->getJson('/api/gastos/filtros/obras')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nave->id)
            ->assertJsonPath('data.0.nombre_trabajo', 'Piso industrial nave 3')
            ->json('data.0');
        $this->assertIsArray($obras);
        $this->assertSame(['id', 'numero', 'nombre_trabajo'], array_keys($obras));
        $this->assertIsString($obras['numero']);

        $proveedores = $this->actingAs($marta)->getJson('/api/gastos/filtros/proveedores')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->proveedor()->id)
            ->assertJsonPath('data.0.nombre', 'Corralón Centro')
            ->assertJsonPath('data.0.es_generico', false)
            ->json('data.0');
        $this->assertIsArray($proveedores);
        $this->assertSame(['id', 'nombre', 'cuit', 'es_generico'], array_keys($proveedores));
    }

    public function test_sin_gastos_los_filtros_dan_403_no_tenes_acceso_a_gastos(): void
    {
        $sofia = $this->sofia();

        foreach (['obras', 'proveedores'] as $filtro) {
            $this->actingAs($sofia)->getJson("/api/gastos/filtros/{$filtro}")->assertForbidden()
                ->assertJsonPath('message', 'No tenés acceso a Gastos. Si lo necesitás, pedíselo a gerencia.');
        }
    }

    public function test_solo_sus_obras_no_trae_obras_ni_proveedores_ajenos(): void
    {
        $laura = $this->laura();
        $nave = $this->obra('Piso industrial nave 3', $laura);
        $losa = $this->obra('Reparación de losa tanque B');
        $diego = $this->diego();
        $this->cargado($diego, $this->factura($this->proveedor(), $nave, '3-1'));
        $this->cargado($diego, $this->factura($this->proveedor('Hotel Puerto Ejemplo'), $losa, '5-3310'));

        $this->actingAs($laura)->getJson('/api/gastos/filtros/obras')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $nave->id);
        $this->actingAs($laura)->getJson('/api/gastos/filtros/obras?buscar=losa')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($laura)->getJson('/api/gastos/filtros/proveedores')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'Corralón Centro');
        $this->actingAs($laura)->getJson('/api/gastos/filtros/proveedores?buscar=hotel')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_proveedores_del_filtro_son_los_que_tienen_gastos_incluido_el_generico(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $this->proveedor('Ferretería Sin Gastos');
        $this->cargado($this->diego(), $this->factura($this->proveedor(), $nave, '3-1'));
        $this->cargado($this->diego(), [
            'tipo_documento' => 'sin_documento', 'origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura',
            'proveedor_id' => null, 'renglones' => [$this->renglon('Combustible camioneta', '1', '68000.00', $nave, 'logistica')],
        ]);

        $this->actingAs($this->marta())->getJson('/api/gastos/filtros/proveedores')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nombre', 'Corralón Centro')
            ->assertJsonPath('data.1.nombre', 'Gastos sin comprobante')
            ->assertJsonPath('data.1.es_generico', true);
    }

    public function test_la_obra_21_se_encuentra_buscandola_y_con_alcance_propio_se_filtra_antes_de_cortar(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            $this->obra(sprintf('Obra ajena %02d', $i));
        }
        $laura = $this->laura();
        $suya = $this->obra('Zócalo de la nave 9', $laura);

        $this->actingAs($this->marta())->getJson('/api/gastos/filtros/obras')->assertOk()->assertJsonCount(20, 'data');
        $this->actingAs($this->marta())->getJson('/api/gastos/filtros/obras?buscar=ajena 22')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre_trabajo', 'Obra ajena 22');
        $this->actingAs($laura)->getJson('/api/gastos/filtros/obras')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $suya->id);
    }

    public function test_el_proveedor_21_se_encuentra_buscandolo(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();

        for ($i = 1; $i <= 21; $i++) {
            $this->cargado($diego, $this->factura($this->proveedor(sprintf('Proveedor %02d', $i)), $nave, "1-{$i}"));
        }

        $this->actingAs($this->marta())->getJson('/api/gastos/filtros/proveedores')->assertOk()
            ->assertJsonCount(20, 'data')->assertJsonPath('data.0.nombre', 'Proveedor 01');
        $this->actingAs($this->marta())->getJson('/api/gastos/filtros/proveedores?buscar=proveedor 21')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'Proveedor 21');
    }

    /**
     * @return array<string, mixed>
     */
    private function factura(Proveedor $proveedor, Obra $obra, string $numero): array
    {
        return [
            'tipo_documento' => 'factura', 'origen' => 'factura', 'numero' => $numero, 'fecha' => '2026-10-17',
            'proveedor_id' => $proveedor->id, 'renglones' => [$this->renglon('Cemento x 50 kg', '30', '9800.00', $obra, 'materiales')],
        ];
    }
}
