<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

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

    public function test_buscar_mas_largo_que_el_maximo_da_422(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->getJson('/api/usuarios/opciones?buscar='.str_repeat('a', 191))
            ->assertUnprocessable()->assertJsonValidationErrors(['buscar']);
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/obras/clientes?buscar='.str_repeat('a', 101))
            ->assertUnprocessable()->assertJsonValidationErrors(['buscar']);
        $this->actingAs($admin)->getJson('/api/usuarios/opciones?buscar='.str_repeat('a', 190))->assertOk();
    }
}
