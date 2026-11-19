<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Obras\Domain\Models\Obra;

/** TL-09: el texto de búsqueda se valida en el borde (largo máximo) y sus % y _ se buscan literales. */
final class BusquedaDeUsuariosTest extends IdentidadTestCase
{
    public function test_buscar_usuarios_toma_porcentaje_y_guion_bajo_como_texto(): void
    {
        $admin = $this->administrador();
        $conGuion = $this->usuarioDe('Compras', ['nombre' => 'Ana_Paz', 'email' => 'ana_paz@empresa-ejemplo.com']);
        $this->usuarioDe('Compras', ['nombre' => 'Anabel', 'email' => 'anabel@empresa-ejemplo.com']);

        $this->actingAs($admin)->getJson('/api/usuarios?buscar=Ana_')->assertOk()->assertJsonPath('data.*.id', [$conGuion->id]);
        $this->actingAs($admin)->getJson('/api/usuarios?buscar=%25')->assertOk()->assertJsonPath('data', []);
        $this->actingAs($admin)->getJson('/api/usuarios/opciones?buscar=Ana_')->assertOk()->assertJsonPath('data.*.id', [$conGuion->id]);
        $this->actingAs($admin)->getJson('/api/usuarios/opciones?buscar=%25')->assertOk()->assertJsonPath('data', []);
    }

    /** DU-P20 · RN-PED-03: el desplegable de propietario lista las personas activas del sector elegido. */
    public function test_opciones_de_usuarios_filtran_por_sector(): void
    {
        $admin = $this->administrador();
        $deCompras = $this->usuarioDe('Compras', ['nombre' => 'Carla', 'email' => 'carla@empresa-ejemplo.com']);
        $this->usuarioDe('Calidad', ['nombre' => 'Sofía', 'email' => 'sofia@empresa-ejemplo.com']);
        $this->usuarioDe('Compras', ['nombre' => 'Marta', 'email' => 'marta@empresa-ejemplo.com', 'activo' => false]);

        $this->actingAs($admin)->getJson("/api/usuarios/opciones?sector_id={$deCompras->sector_id}")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$deCompras->id]);
    }

    /** PU-118: un buscador o una sugerencia nunca fallan por largo; el texto se recorta al máximo. */
    public function test_buscar_mas_largo_que_el_maximo_se_recorta_y_no_da_422(): void
    {
        $admin = $this->administrador();
        $cliente = str_repeat('Ñandú ', 25);
        Obra::factory()->create(['cliente' => $cliente]);

        $this->actingAs($admin)->getJson('/api/obras/clientes?buscar='.rawurlencode($cliente.' y algo más'))
            ->assertOk()
            ->assertJsonPath('data', [$cliente]);

        foreach (['/api/usuarios/opciones?buscar=', '/api/usuarios?buscar=', '/api/obras?buscar=', '/api/proveedores?buscar=', '/api/proveedores/opciones?buscar=', '/api/buscar?q='] as $ruta) {
            $this->actingAs($admin)->getJson($ruta.str_repeat('a', 400))->assertOk();
        }
    }
}
