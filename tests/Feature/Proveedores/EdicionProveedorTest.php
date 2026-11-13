<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Domain\Models\Proveedor;

/** F-56 · W-38 · HU-021 crit. 5 y 6 (RN-PRV-04, 07, 11, 13). */
final class EdicionProveedorTest extends ProveedoresTestCase
{
    public function test_edicion_guarda_los_cambios_con_quien_y_cuando(): void
    {
        $proveedor = Proveedor::factory()->llamado('Transportes Ejemplo')->deRubro('logistica')->create();
        $carla = $this->quienEdita();

        $this->actingAs($carla)->patchJson("/api/proveedores/{$proveedor->id}", [
            'nombre' => 'Transporte Ejemplo',
            'cuit' => '30-00000001-5',
            'telefono' => '291 000-0004',
        ])->assertOk()
            ->assertJsonPath('message', 'Guardamos los cambios.')
            ->assertJsonPath('data.nombre', 'Transporte Ejemplo')
            ->assertJsonPath('data.cuit', '30-00000001-5')
            ->assertJsonPath('data.telefono', '291 000-0004')
            ->assertJsonPath('data.rubro_habitual.clave', 'logistica');

        $proveedor->refresh();
        $this->assertSame($carla->id, $proveedor->updated_by);
        $this->assertSame('transporte ejemplo', $proveedor->nombre_clave);

        $actividad = $this->actividadDe($proveedor, 'editado');
        $this->assertNotNull($actividad);
        $this->assertSame($carla->id, $actividad->user_id);
        $this->assertSame([
            'nombre' => ['antes' => 'Transportes Ejemplo', 'despues' => 'Transporte Ejemplo'],
            'cuit' => ['antes' => null, 'despues' => '30000000015'],
            'telefono' => ['antes' => null, 'despues' => '291 000-0004'],
        ], $actividad->cambios);
    }

    public function test_guardar_sin_cambios_no_registra_actividad(): void
    {
        $proveedor = $this->proveedor('Ferretería Norte');

        $this->actingAs($this->quienEdita())->patchJson("/api/proveedores/{$proveedor->id}", ['nombre' => 'Ferretería Norte'])->assertOk();

        $this->assertNull($this->actividadDe($proveedor, 'editado'));
    }

    public function test_se_pueden_vaciar_los_datos_opcionales(): void
    {
        $proveedor = Proveedor::factory()->llamado('Corralón Centro')->conCuit('30000000007')->deRubro('materiales')
            ->create(['email' => 'ventas@corralon-centro.com']);

        $this->actingAs($this->quienEdita())->patchJson("/api/proveedores/{$proveedor->id}", ['cuit' => null, 'email' => '', 'rubro_habitual' => null])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Corralón Centro')
            ->assertJsonPath('data.cuit', null)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.rubro_habitual', null);
    }

    public function test_al_editar_el_nombre_no_choca_consigo_mismo_pero_si_con_otro(): void
    {
        $hormigonera = $this->proveedor('Hormigonera Sur SRL');
        $corralon = $this->proveedor('Corralón Centro', '30000000007');
        $user = $this->quienEdita();

        $this->actingAs($user)->patchJson("/api/proveedores/{$hormigonera->id}", ['nombre' => 'Hormigonera Sur S.R.L.'])
            ->assertOk()->assertJsonPath('data.nombre', 'Hormigonera Sur S.R.L.');

        $this->actingAs($user)->patchJson("/api/proveedores/{$corralon->id}", ['nombre' => 'HORMIGONERA SUR'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'Ya existe Hormigonera Sur S.R.L.')
            ->assertJsonPath('errors.existente.id', $hormigonera->id);

        $this->actingAs($user)->patchJson("/api/proveedores/{$hormigonera->id}", ['cuit' => '30000000007'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cuit.0', 'Ese CUIT ya es de Corralón Centro.');

        $this->actingAs($user)->patchJson("/api/proveedores/{$corralon->id}", ['cuit' => '30-00000000-7'])->assertOk();
    }

    public function test_el_nombre_no_se_puede_vaciar_al_editar(): void
    {
        $proveedor = $this->proveedor('Ferretería Norte');

        $this->actingAs($this->quienEdita())->patchJson("/api/proveedores/{$proveedor->id}", ['nombre' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'Completá este campo.');
    }

    public function test_proveedor_generico_no_se_edita(): void
    {
        $generico = app(ProveedoresConsulta::class)->generico();

        $this->actingAs($this->quienEdita())->patchJson("/api/proveedores/{$generico->valor}", ['nombre' => 'Gastos varios'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.proveedor.0', 'El proveedor genérico no se modifica.');

        $this->assertDatabaseHas('proveedores', ['id' => $generico->valor, 'nombre' => 'Gastos sin comprobante']);
        $this->actingAs($this->quienEdita())->getJson("/api/proveedores/{$generico->valor}")
            ->assertJsonPath('data.es_generico', true)
            ->assertJsonPath('data.puede.editar', false);
    }

    public function test_solo_proveedores_editar_edita(): void
    {
        $proveedor = $this->proveedor('Ferretería Norte');

        $this->actingAs($this->quienVe())->patchJson("/api/proveedores/{$proveedor->id}", ['nombre' => 'Ferretería Sur'])->assertForbidden();
        $this->actingAs($this->sinAcceso())->patchJson("/api/proveedores/{$proveedor->id}", ['nombre' => 'Ferretería Sur'])->assertForbidden();
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'nombre' => 'Ferretería Norte']);

        $this->actingAs($this->usuarioDe('Jefatura de Administración'))->patchJson("/api/proveedores/{$proveedor->id}", ['nombre' => 'Ferretería Sur'])->assertOk();
    }

    public function test_editar_un_proveedor_que_no_existe_es_404(): void
    {
        $this->actingAs($this->quienEdita())->patchJson('/api/proveedores/999999', ['nombre' => 'Otro'])->assertNotFound();
    }

    public function test_no_hay_baja_de_proveedores(): void
    {
        $proveedor = $this->proveedor('Ferretería Norte');

        $this->actingAs($this->quienEdita())->deleteJson("/api/proveedores/{$proveedor->id}")->assertStatus(405);
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }
}
