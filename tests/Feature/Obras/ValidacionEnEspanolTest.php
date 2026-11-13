<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use Tests\Feature\Obras\Support\ObrasTestCase;
use Tests\Support\TextoEnEspanol;

/** Reglas sin mensaje propio en Obras: responden con lang/es y el nombre del campo en palabras del cliente. */
final class ValidacionEnEspanolTest extends ObrasTestCase
{
    use TextoEnEspanol;

    public function test_filtro_de_estado_invalido_en_el_listado_se_explica_en_espanol(): void
    {
        $response = $this->actingAs($this->laura())->getJson('/api/obras?estado=archivada&page=0')
            ->assertUnprocessable()
            ->assertJsonPath('errors.estado', ['El valor elegido en estado no es válido.'])
            ->assertJsonPath('errors.page', ['El campo página tiene que ser al menos 1.']);

        $this->assertRespuestaSinIngles($response);
    }

    public function test_montos_por_rubro_en_modo_solo_total_nombran_campo_y_valor_en_palabras_del_cliente(): void
    {
        $diego = $this->diego();
        $obra = Obra::factory()->deResponsable($diego)->create();
        $principal = Presupuesto::query()->where('obra_id', $obra->id)->sole();

        $response = $this->actingAs($diego)->putJson("/api/presupuestos/{$principal->id}/montos", [
            'modo' => 'solo_total',
            'total' => '1000.00',
            'montos' => ['materiales' => '10.00'],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.montos', ['El campo montos por rubro solo se puede cargar cuando forma de carga es por rubro.']);

        $this->assertRespuestaSinIngles($response);
    }

    public function test_cambio_de_estado_a_uno_que_no_existe_se_explica_en_espanol(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($diego);

        $response = $this->actingAs($diego)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'pausada'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.estado', ['El valor elegido en estado no es válido.']);

        $this->assertRespuestaSinIngles($response);
    }
}
