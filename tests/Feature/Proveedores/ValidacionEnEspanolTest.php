<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use Tests\Support\TextoEnEspanol;

/**
 * Proveedores en español de punta a punta: cada regla tiene mensaje propio del módulo y lo que no lo
 * tiene (atributos en :attribute, 404) sale de lang/es.
 */
final class ValidacionEnEspanolTest extends ProveedoresTestCase
{
    use TextoEnEspanol;

    public function test_alta_con_todos_los_campos_invalidos_responde_solo_en_espanol(): void
    {
        $response = $this->actingAs($this->quienEdita())->postJson('/api/proveedores', [
            'nombre' => ['Proveedor Ejemplo'],
            'cuit' => str_repeat('9', 25),
            'telefono' => ['1'],
            'email' => 'no-es-un-mail',
            'rubro_habitual' => 'no_existe',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre', ['Tiene que ser texto.'])
            ->assertJsonPath('errors.email', ['El mail no es válido.'])
            ->assertJsonPath('errors.rubro_habitual', ['No existe o no está disponible.']);

        $this->assertRespuestaSinIngles($response);
    }

    public function test_regla_sin_mensaje_propio_nombra_el_campo_en_palabras_del_cliente(): void
    {
        $validator = validator(['cuit' => ['30'], 'excluir_id' => 'x'], ['cuit' => ['string'], 'excluir_id' => ['integer']]);

        $this->assertSame([
            'cuit' => ['El campo CUIT tiene que ser texto.'],
            'excluir_id' => ['El campo proveedor a excluir tiene que ser un número entero.'],
        ], $validator->errors()->toArray());
    }

    public function test_proveedor_inexistente_da_404_en_espanol(): void
    {
        $response = $this->actingAs($this->quienVe())->getJson('/api/proveedores/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'No encontramos lo que buscabas.');

        $this->assertRespuestaSinIngles($response);
    }
}
