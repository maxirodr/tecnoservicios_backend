<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Models\Sector;
use Tests\Support\TextoEnEspanol;

/** Reglas sin mensaje propio en Identidad: responden con lang/es y el nombre del campo en palabras del cliente. */
final class ValidacionEnEspanolTest extends IdentidadTestCase
{
    use TextoEnEspanol;

    public function test_nivel_y_alcance_invalidos_en_la_tabla_de_permisos_se_explican_en_espanol(): void
    {
        $sector = Sector::query()->where('nombre', 'Compras')->sole();

        $response = $this->actingAs($this->administrador())->putJson('/api/permisos', [
            'celdas' => [['sector_id' => $sector->id, 'funcion' => 'obras', 'nivel' => 'total', 'alcance' => 'algunas']],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'celdas.0.nivel' => ['El valor elegido en nivel de acceso no es válido.'],
                'celdas.0.alcance' => ['El valor elegido en alcance no es válido.'],
            ]);

        $this->assertRespuestaSinIngles($response);
    }
}
