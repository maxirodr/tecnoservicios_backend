<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;
use Tests\Support\TextoEnEspanol;

/** Reglas sin mensaje propio en adjuntos: responden con lang/es y el nombre del campo en palabras del cliente. */
final class ValidacionEnEspanolTest extends AdjuntosTestCase
{
    use TextoEnEspanol;

    public function test_nombre_del_link_que_no_es_texto_se_explica_en_espanol(): void
    {
        $response = $this->actingAs($this->usuario())->postJson('/api/adjuntos', [
            'contexto' => self::CONTEXTO,
            'url' => 'https://drive.example/oc-ejemplo',
            'nombre' => ['OC ejemplo'],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors', ['nombre' => ['El campo nombre tiene que ser texto.']]);

        $this->assertRespuestaSinIngles($response);
    }
}
