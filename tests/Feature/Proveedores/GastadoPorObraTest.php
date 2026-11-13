<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Proveedores\Application\Ports\FilaGastado;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Application\Ports\ObraGastada;
use Tests\Support\PuertoGastadoEspia;

/** F-57 · W-14 "Gastado por obra" (RN-PRV-15…17): contrato en semana 1, lo llena Gastos en semana 3. */
final class GastadoPorObraTest extends ProveedoresTestCase
{
    public function test_gastado_por_obra_vacio_en_semana_1(): void
    {
        $proveedor = $this->proveedor('Corralón Centro');

        $this->actingAs($this->quienVe())->getJson("/api/proveedores/{$proveedor->id}/gastado-por-obra")
            ->assertOk()
            ->assertJsonPath('data', ['filas' => [], 'total' => ['renglones' => 0, 'importe' => '0.00']]);
    }

    public function test_gastado_por_obra_exige_permiso_de_gastos(): void
    {
        $proveedor = $this->proveedor('Corralón Centro');
        // Proveedores = ver pero Gastos sin acceso.
        $this->celda(self::SECTOR_VE, 'gastos', 'sin_acceso');

        $this->actingAs($this->quienVe())->getJson("/api/proveedores/{$proveedor->id}/gastado-por-obra")->assertForbidden();
        $this->actingAs($this->quienVe())->getJson("/api/proveedores/{$proveedor->id}")->assertJsonPath('data.puede.ver_gastado', false);
        $this->actingAs($this->sinAcceso())->getJson("/api/proveedores/{$proveedor->id}/gastado-por-obra")->assertForbidden();
    }

    public function test_gastado_por_obra_de_un_proveedor_que_no_existe_es_404(): void
    {
        $this->actingAs($this->quienVe())->getJson('/api/proveedores/999999/gastado-por-obra')->assertNotFound();
    }

    public function test_gastado_por_obra_con_alcance_todas_muestra_sin_obra_y_total(): void
    {
        $espia = $this->puertoConDatos();
        $proveedor = $this->proveedor('Corralón Centro');

        $this->actingAs($this->quienVe())->getJson("/api/proveedores/{$proveedor->id}/gastado-por-obra")
            ->assertOk()
            ->assertJsonCount(2, 'data.filas')
            ->assertJsonPath('data.filas.0.obra', ['id' => 12, 'numero' => '2-131-2026', 'nombre_trabajo' => 'Piso industrial nave 3'])
            ->assertJsonPath('data.filas.1.obra', null)
            ->assertJsonPath('data.total', ['renglones' => 5, 'importe' => '150.50']);

        $this->assertTrue($espia->alcance?->esTodas());
        $this->assertSame($proveedor->id, $espia->proveedor?->valor);
    }

    public function test_gastado_por_obra_con_alcance_propias_muestra_solo_sus_obras(): void
    {
        $espia = $this->puertoConDatos();
        $proveedor = $this->proveedor('Corralón Centro');
        $this->celda(self::SECTOR_VE, 'gastos', 'ver', 'propias');
        $user = $this->quienVe();

        $this->actingAs($user)->getJson("/api/proveedores/{$proveedor->id}/gastado-por-obra")
            ->assertOk()
            ->assertJsonCount(1, 'data.filas')
            ->assertJsonPath('data.filas.0.obra.id', 12)
            ->assertJsonPath('data.total', ['renglones' => 4, 'importe' => '100.25']);

        $this->assertSame($user->id, $espia->alcance?->userId);
    }

    /** Puerto falso con importes ficticios: una obra y la fila "(sin obra)". */
    private function puertoConDatos(): PuertoGastadoEspia
    {
        $espia = new PuertoGastadoEspia(new GastadoPorObra([
            new FilaGastado(new ObraGastada(12, '2-131-2026', 'Piso industrial nave 3'), 4, '100.25'),
            new FilaGastado(null, 1, '50.25'),
        ]));
        $this->app->instance(GastadoPorProveedor::class, $espia);

        return $espia;
    }
}
