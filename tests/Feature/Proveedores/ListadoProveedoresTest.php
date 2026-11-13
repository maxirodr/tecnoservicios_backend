<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;

/** F-55 · W-14 · HU-020 (RN-PRV-01…03, D-040). */
final class ListadoProveedoresTest extends ProveedoresTestCase
{
    public function test_listado_ordena_alfabeticamente_sin_tildes(): void
    {
        $this->proveedor('Transportes Ejemplo');
        $this->proveedor('Ángulo Metales Ejemplo');
        $this->proveedor('corralón Centro');
        $this->proveedor('Alquileres Andamios Oeste');

        $response = $this->actingAs($this->quienVe())->getJson('/api/proveedores')->assertOk();

        $this->assertSame(
            ['Alquileres Andamios Oeste', 'Ángulo Metales Ejemplo', 'corralón Centro', 'Gastos sin comprobante', 'Transportes Ejemplo'],
            array_column((array) $response->json('data'), 'nombre'),
        );
    }

    public function test_busca_por_parte_del_nombre_sin_tildes_ni_puntuacion(): void
    {
        $this->proveedor('Corralón Centro');
        $this->proveedor('Hormigonera Sur S.R.L.');
        $this->proveedor('Hotel Puerto Ejemplo');
        $this->proveedor('Ferretería Norte');
        $user = $this->quienVe();

        $this->assertSame(['Corralón Centro'], $this->nombres($user, 'corralon'));
        $this->assertSame(['Hormigonera Sur S.R.L.'], $this->nombres($user, 'hormig'));
        $this->assertSame(['Hormigonera Sur S.R.L.'], $this->nombres($user, 's.r.l'));
        $this->assertSame(['Hormigonera Sur S.R.L.'], $this->nombres($user, 'SRL'));
        $this->assertSame(['Hotel Puerto Ejemplo'], $this->nombres($user, 'hotel puerto'));
        $this->assertSame(['Ferretería Norte'], $this->nombres($user, 'FERRETERIA'));
        $this->assertSame([], $this->nombres($user, '100%'));
    }

    public function test_listado_trae_cuit_rubro_habitual_y_marca_generico_paginado(): void
    {
        Proveedor::factory()->llamado('Corralón Centro')->conCuit('30000000007')->deRubro('materiales')->create();
        Proveedor::factory()->count(30)->create();

        $response = $this->actingAs($this->quienVe())->getJson('/api/proveedores?buscar=corral')->assertOk();

        $response->assertJsonPath('data.0', [
            'id' => Proveedor::query()->where('nombre', 'Corralón Centro')->valueOrFail('id'),
            'nombre' => 'Corralón Centro',
            'cuit' => '30-00000000-7',
            'rubro_habitual' => ['clave' => 'materiales', 'nombre' => 'MATERIALES'],
            'es_generico' => false,
        ]);

        $todos = $this->actingAs($this->quienVe())->getJson('/api/proveedores')->assertOk();
        $todos->assertJsonPath('meta.total', 32)->assertJsonPath('meta.per_page', 25)->assertJsonCount(25, 'data');
        $this->actingAs($this->quienVe())->getJson('/api/proveedores?page=2')->assertJsonCount(7, 'data');

        $generico = $this->actingAs($this->quienVe())->getJson('/api/proveedores?buscar=sin comprobante');
        $generico->assertJsonPath('data.0.es_generico', true)->assertJsonPath('data.0.cuit', null);
    }

    public function test_proveedor_no_expone_saldos_ni_cuenta_corriente(): void
    {
        $proveedor = $this->proveedor('Corralón Centro', '30000000007');
        $user = $this->quienVe();

        $item = (array) $this->actingAs($user)->getJson('/api/proveedores')->json('data.0');
        $ficha = (array) $this->actingAs($user)->getJson("/api/proveedores/{$proveedor->id}")->assertOk()->json('data');

        foreach (['saldo', 'cuenta_corriente', 'condicion_pago', 'vencimientos', 'estado_pago', 'adjuntos', 'link'] as $clave) {
            $this->assertArrayNotHasKey($clave, $item);
            $this->assertArrayNotHasKey($clave, $ficha);
        }
    }

    public function test_ficha_trae_sus_datos_y_lo_que_puede_hacer_quien_mira(): void
    {
        $proveedor = Proveedor::factory()->llamado('Corralón Centro')->conCuit('30000000007')->deRubro('materiales')
            ->create(['telefono' => '291 000-0002', 'email' => 'ventas@corralon-centro.com']);

        $this->actingAs($this->quienVe())->getJson("/api/proveedores/{$proveedor->id}")
            ->assertOk()
            ->assertJsonPath('data', [
                'id' => $proveedor->id,
                'nombre' => 'Corralón Centro',
                'cuit' => '30-00000000-7',
                'rubro_habitual' => ['clave' => 'materiales', 'nombre' => 'MATERIALES'],
                'es_generico' => false,
                'telefono' => '291 000-0002',
                'email' => 'ventas@corralon-centro.com',
                'puede' => ['editar' => false, 'ver_gastado' => true],
            ]);

        $this->actingAs($this->quienEdita())->getJson("/api/proveedores/{$proveedor->id}")
            ->assertJsonPath('data.puede', ['editar' => true, 'ver_gastado' => true]);
    }

    public function test_sin_permiso_de_proveedores_devuelve_403(): void
    {
        $proveedor = $this->proveedor('Corralón Centro');
        $laura = $this->sinAcceso();

        $this->actingAs($laura)->getJson('/api/proveedores')->assertForbidden()->assertJsonPath('data', null);
        $this->actingAs($laura)->getJson("/api/proveedores/{$proveedor->id}")->assertForbidden()->assertJsonMissingPath('data.nombre');
    }

    public function test_todos_los_endpoints_exigen_sesion(): void
    {
        $this->getJson('/api/proveedores')->assertUnauthorized();
        $this->getJson('/api/proveedores/1')->assertUnauthorized();
        $this->getJson('/api/proveedores/opciones')->assertUnauthorized();
        $this->getJson('/api/proveedores/parecidos?nombre=corralon')->assertUnauthorized();
        $this->getJson('/api/proveedores/1/gastado-por-obra')->assertUnauthorized();
        $this->postJson('/api/proveedores', ['nombre' => 'Proveedor Sin Sesion'])->assertUnauthorized();
        $this->patchJson('/api/proveedores/1', ['nombre' => 'Proveedor Sin Sesion'])->assertUnauthorized();
        $this->getJson('/api/buscar?q=corralon')->assertUnauthorized();
    }

    public function test_ficha_de_un_proveedor_que_no_existe_es_404(): void
    {
        $this->actingAs($this->quienVe())->getJson('/api/proveedores/999999')->assertNotFound();
    }

    public function test_todos_los_sectores_con_ver_o_editar_acceden_a_la_base(): void
    {
        foreach (['Dirección', 'Gerencia de Operaciones', 'Jefatura de Proyectos', 'Gerencia de Administración', 'Jefatura de Administración', 'Compras'] as $sector) {
            $this->actingAs($this->usuarioDe($sector))->getJson('/api/proveedores')->assertOk();
        }

        foreach (['Calidad', 'Seguridad e Higiene', 'RRHH', 'Pañol y Logística', 'Ingeniería de proyectos'] as $sector) {
            $this->actingAs($this->usuarioDe($sector))->getJson('/api/proveedores')->assertForbidden();
        }
    }

    /**
     * @return list<string>
     */
    private function nombres(User $user, string $buscar): array
    {
        $data = (array) $this->actingAs($user)->getJson('/api/proveedores?buscar='.urlencode($buscar))->assertOk()->json('data');

        return array_column($data, 'nombre');
    }
}
