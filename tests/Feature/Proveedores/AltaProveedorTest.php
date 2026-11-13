<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Proveedores\Domain\Models\Proveedor;
use Illuminate\Support\Str;

/** F-56 · F-42 (alta rápida, mismo endpoint) · W-38 · HU-021 (RN-PRV-04…07, 10). */
final class AltaProveedorTest extends ProveedoresTestCase
{
    public function test_alta_solo_con_nombre(): void
    {
        $carla = $this->quienEdita();

        $response = $this->actingAs($carla)->postJson('/api/proveedores', ['nombre' => '  Ferretería   Norte '])
            ->assertCreated()
            ->assertJsonPath('message', 'Guardamos Ferretería Norte.')
            ->assertJsonPath('data.nombre', 'Ferretería Norte')
            ->assertJsonPath('data.cuit', null)
            ->assertJsonPath('data.telefono', null)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.rubro_habitual', null)
            ->assertJsonPath('data.puede.editar', true);

        $proveedor = Proveedor::query()->where('nombre', 'Ferretería Norte')->sole();
        $response->assertJsonPath('data.id', $proveedor->id);
        $this->assertSame($carla->id, $proveedor->created_by);
        $this->assertSame('ferreteria norte', $proveedor->nombre_clave);
        $actividad = $this->actividadDe($proveedor, 'creado');
        $this->assertNotNull($actividad);
        $this->assertSame($carla->id, $actividad->user_id);
    }

    public function test_alta_con_todos_los_datos(): void
    {
        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', [
            'nombre' => 'Hormigonera del Sur',
            'cuit' => '30-00000001-5',
            'telefono' => '291 000-0003',
            'email' => 'ventas@hormigonera-ejemplo.com',
            'rubro_habitual' => 'materiales',
        ])->assertCreated()
            ->assertJsonPath('data.cuit', '30-00000001-5')
            ->assertJsonPath('data.telefono', '291 000-0003')
            ->assertJsonPath('data.email', 'ventas@hormigonera-ejemplo.com')
            ->assertJsonPath('data.rubro_habitual', ['clave' => 'materiales', 'nombre' => 'MATERIALES']);
    }

    public function test_cuit_se_normaliza_a_once_digitos(): void
    {
        $user = $this->quienEdita();

        foreach (['Proveedor Uno' => '30-00000001-5', 'Proveedor Dos' => '30 00000000 7', 'Proveedor Tres' => '20000000028'] as $nombre => $cuit) {
            $this->actingAs($user)->postJson('/api/proveedores', ['nombre' => $nombre, 'cuit' => $cuit])->assertCreated();
        }

        $this->assertSame(['20000000028', '30000000007', '30000000015'], Proveedor::query()->whereNotNull('cuit')->orderBy('cuit')->pluck('cuit')->all());
    }

    public function test_cuit_con_digito_verificador_invalido_es_rechazado(): void
    {
        $user = $this->quienEdita();

        foreach (['30-00000001-1', '3000000001', '300000000155', '30-0000000A-5'] as $cuit) {
            $this->actingAs($user)->postJson('/api/proveedores', ['nombre' => 'Proveedor Ejemplo', 'cuit' => $cuit])
                ->assertUnprocessable()
                ->assertJsonPath('errors.cuit.0', 'El CUIT no es válido. Revisá los números.');
        }

        $this->assertSame(0, Proveedor::query()->where('es_generico', false)->count());
    }

    public function test_cuit_no_se_repite(): void
    {
        $corralon = $this->proveedor('Corralón Centro', '30000000007');

        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'Corralon Centro Sucursal', 'cuit' => '30-00000000-7'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.cuit.0', 'Ese CUIT ya es de Corralón Centro.')
            ->assertJsonPath('errors.existente', ['id' => $corralon->id, 'nombre' => 'Corralón Centro']);

        $this->assertDatabaseMissing('proveedores', ['nombre' => 'Corralon Centro Sucursal']);
    }

    public function test_no_permite_dos_proveedores_con_el_mismo_nombre_normalizado(): void
    {
        $existente = $this->proveedor('Hormigonera Sur SRL');
        $user = $this->quienEdita();

        foreach (['HORMIGONERA SUR', 'Hormigonera Sur S.R.L.', 'hormigonera sur srl', 'Hormigonera  Súr S. R. L.'] as $nombre) {
            $this->actingAs($user)->postJson('/api/proveedores', ['nombre' => $nombre])
                ->assertUnprocessable()
                ->assertJsonPath('errors.nombre.0', 'Ya existe Hormigonera Sur SRL.')
                ->assertJsonPath('errors.existente', ['id' => $existente->id, 'nombre' => 'Hormigonera Sur SRL']);
        }

        $this->assertSame(1, Proveedor::query()->where('nombre_clave', 'hormigonera sur')->count());
    }

    public function test_parecidos_no_bloquean_el_alta(): void
    {
        $this->proveedor('Hormigonera Sur SRL');
        $this->proveedor('Hormigones del Sur');

        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'Hormigonera del Sur'])->assertCreated();
    }

    public function test_email_se_guarda_en_minusculas(): void
    {
        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'Corralón Centro', 'email' => ' VENTAS@Corralon-Centro.com '])
            ->assertCreated()
            ->assertJsonPath('data.email', 'ventas@corralon-centro.com');
    }

    public function test_email_y_rubro_invalidos_son_rechazados(): void
    {
        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'Proveedor Ejemplo', 'email' => 'no-es-un-mail', 'rubro_habitual' => 'insumo'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'El mail no es válido.')
            ->assertJsonPath('errors.rubro_habitual.0', 'No existe o no está disponible.');
    }

    public function test_nombre_es_obligatorio_y_hasta_150_caracteres(): void
    {
        $user = $this->quienEdita();

        $this->actingAs($user)->postJson('/api/proveedores', ['nombre' => '   '])->assertUnprocessable()->assertJsonPath('errors.nombre.0', 'Completá este campo.');
        $this->actingAs($user)->postJson('/api/proveedores', ['cuit' => '30000000007'])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $this->actingAs($user)->postJson('/api/proveedores', ['nombre' => Str::repeat('a', 151)])->assertUnprocessable()->assertJsonValidationErrors('nombre');
    }

    public function test_rubro_habitual_epp_es_valido(): void
    {
        $this->actingAs($this->quienEdita())->postJson('/api/proveedores', ['nombre' => 'Indumentaria Ejemplo', 'rubro_habitual' => 'epp'])
            ->assertCreated()
            ->assertJsonPath('data.rubro_habitual', ['clave' => 'epp', 'nombre' => 'EPP']);
    }

    public function test_proveedor_ignora_adjuntos_y_links_en_el_payload(): void
    {
        $response = $this->actingAs($this->quienEdita())->postJson('/api/proveedores', [
            'nombre' => 'Transportes Ejemplo',
            'adjuntos' => [1, 2],
            'factura_url' => 'https://drive.example.com/factura',
            'saldo' => '0.00',
        ])->assertCreated();

        $data = (array) $response->json('data');
        $this->assertArrayNotHasKey('adjuntos', $data);
        $this->assertArrayNotHasKey('factura_url', $data);
        $this->assertArrayNotHasKey('saldo', $data);
    }

    public function test_alta_con_idempotency_key_no_duplica(): void
    {
        $user = $this->quienEdita();
        $clave = (string) Str::uuid();
        $payload = ['nombre' => 'Ferretería Norte'];

        $primera = $this->actingAs($user)->withHeader('Idempotency-Key', $clave)->postJson('/api/proveedores', $payload)->assertCreated();
        $segunda = $this->actingAs($user)->withHeader('Idempotency-Key', $clave)->postJson('/api/proveedores', $payload)->assertCreated();

        $segunda->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame($primera->json('data.id'), $segunda->json('data.id'));
        $this->assertSame(1, Proveedor::query()->where('nombre', 'Ferretería Norte')->count());
    }

    public function test_solo_proveedores_editar_da_de_alta(): void
    {
        foreach (['Gerencia de Administración', 'Jefatura de Administración', 'Compras'] as $sector) {
            $this->actingAs($this->usuarioDe($sector))->postJson('/api/proveedores', ['nombre' => "Proveedor de {$sector}"])->assertCreated();
        }

        foreach (['Dirección', 'Gerencia de Operaciones', 'Jefatura de Proyectos', 'Ingeniería de proyectos', 'Calidad'] as $sector) {
            $this->actingAs($this->usuarioDe($sector))->postJson('/api/proveedores', ['nombre' => 'Proveedor Denegado'])->assertForbidden();
        }

        $this->assertDatabaseMissing('proveedores', ['nombre' => 'Proveedor Denegado']);
    }
}
