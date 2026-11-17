<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Proveedores\Domain\Models\Proveedor;

/** PU-043 (DU-Q04): la ñ distingue proveedores. PU-044 (DU-Q05): se busca también por CUIT. */
final class EnieYCuitEnBusquedaTest extends ProveedoresTestCase
{
    public function test_la_enie_no_es_n_para_el_repetido_pero_avisa_como_parecido(): void
    {
        $carla = $this->quienEdita();
        $this->actingAs($carla)->postJson('/api/proveedores', ['nombre' => 'Corralón Ñandú S.R.L.'])->assertCreated();

        $parecidos = (array) $this->actingAs($carla)->getJson('/api/proveedores/parecidos?nombre='.urlencode('Corralon Nandu'))
            ->assertOk()
            ->json('data');
        $this->assertSame(['Corralón Ñandú S.R.L.'], array_column($parecidos, 'nombre'));

        $this->actingAs($carla)->postJson('/api/proveedores', ['nombre' => 'Corralon Nandu'])->assertCreated();
        $this->assertSame(2, Proveedor::query()->where('es_generico', false)->count());
    }

    public function test_el_mismo_nombre_con_enie_sigue_siendo_repetido(): void
    {
        $carla = $this->quienEdita();
        $this->actingAs($carla)->postJson('/api/proveedores', ['nombre' => 'Corralón Ñandú S.R.L.'])->assertCreated();

        $this->actingAs($carla)->postJson('/api/proveedores', ['nombre' => 'corralon ÑANDU srl'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'Ya existe Corralón Ñandú S.R.L.');
    }

    public function test_buscar_sin_enie_encuentra_el_nombre_con_enie(): void
    {
        $this->proveedor('Corralón Ñandú');
        $user = $this->quienVe();

        $this->actingAs($user)->getJson('/api/proveedores?buscar=nandu')->assertOk()->assertJsonPath('data.0.nombre', 'Corralón Ñandú');
        $this->actingAs($user)->getJson('/api/proveedores?buscar='.urlencode('ñandú'))->assertOk()->assertJsonPath('data.0.nombre', 'Corralón Ñandú');
    }

    public function test_busca_por_cuit_con_o_sin_guiones_desde_4_digitos(): void
    {
        $this->proveedor('Corralón Centro', '30712345679');
        $this->proveedor('Ferretería Norte', '20000000001');
        $user = $this->quienVe();

        foreach (['30-71234567-9', '30712345679', '7123', '30 7123', '4567-9'] as $buscar) {
            $data = (array) $this->actingAs($user)->getJson('/api/proveedores?buscar='.urlencode($buscar))->assertOk()->json('data');
            $this->assertSame(['Corralón Centro'], array_column($data, 'nombre'), "Buscando {$buscar}");
        }

        $this->actingAs($user)->getJson('/api/proveedores?buscar=712')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_el_buscador_general_encuentra_proveedores_por_cuit(): void
    {
        $this->proveedor('Corralón Centro', '30712345679');

        $grupos = (array) $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/buscar?q=30-7123')->assertOk()->json('data.grupos');
        $proveedores = collect($grupos)->firstWhere('tipo', 'proveedores');

        $this->assertIsArray($proveedores);
        $this->assertSame(1, $proveedores['cantidad']);
        $this->assertSame('Corralón Centro', $proveedores['resultados'][0]['nombre']);
        $this->assertSame('30-71234567-9', $proveedores['resultados'][0]['cuit'], 'PU-137: el resultado muestra el CUIT con el que coincidió');
    }
}
