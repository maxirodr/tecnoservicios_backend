<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;

/** F-02 · F-10 · W-17 · W-19 · HU-003 (RN-IDE-11…16). */
final class PerfilTest extends IdentidadTestCase
{
    public function test_me_devuelve_las_doce_funciones_con_nivel_y_alcance(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos');

        $response = $this->actingAs($laura)->getJson('/api/me')->assertOk();

        $permisos = $response->json('data.permisos');
        $this->assertIsArray($permisos);
        $this->assertSame(array_column(ClaveFuncion::cases(), 'value'), array_keys($permisos));
        $response->assertJsonPath('data.permisos.rendicion', ['nivel' => 'ver', 'alcance' => 'propias'])
            ->assertJsonPath('data.permisos.proveedores', ['nivel' => 'sin_acceso', 'alcance' => 'todas'])
            ->assertJsonPath('data.permisos.obras', ['nivel' => 'editar', 'alcance' => 'todas']);
    }

    public function test_me_sin_sesion_devuelve_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized()->assertJsonPath('data', null);
    }

    public function test_me_indica_si_tiene_gente_a_cargo(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos');
        $sofia = $this->usuarioDe('Ingeniería de proyectos');
        User::factory()->enSector('Ingeniería de proyectos')->inactivo()->dependeDe($sofia)->create();
        $this->usuarioDe('Ingeniería de proyectos', ['jefe_id' => $hernan->id]);

        $this->actingAs($hernan)->getJson('/api/me')->assertJsonPath('data.tiene_gente', true);
        $this->actingAs($sofia)->getJson('/api/me')->assertJsonPath('data.tiene_gente', false);
    }

    public function test_dato_depende_de_se_ve_en_mi_perfil(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
        $laura = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura', 'jefe_id' => $hernan->id, 'telefono' => '291 000-0000']);

        $this->actingAs($laura)->getJson('/api/me')
            ->assertJsonPath('data.jefe', ['id' => $hernan->id, 'nombre' => 'Hernán', 'sector' => 'Jefatura de Proyectos'])
            ->assertJsonPath('data.telefono', '291 000-0000')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.eligio_contrasena');
    }

    public function test_perfil_permite_cambiar_nombre_y_telefono(): void
    {
        $laura = $this->usuarioDe('Compras', ['nombre' => 'Laura']);

        $this->actingAs($laura)->patchJson('/api/me', ['nombre' => ' Laura B. ', 'telefono' => '291 111-1111'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Laura B.')
            ->assertJsonPath('data.telefono', '291 111-1111');

        $this->assertDatabaseHas('users', ['id' => $laura->id, 'nombre' => 'Laura B.', 'telefono' => '291 111-1111']);
    }

    public function test_perfil_no_permite_cambiar_mail_sector_ni_jefe(): void
    {
        $jefe = $this->usuarioDe('Dirección');
        $laura = $this->usuarioDe('Compras', ['email' => 'laura@empresa-ejemplo.com']);
        $direccion = Sector::query()->where('nombre', 'Dirección')->valueOrFail('id');

        $this->actingAs($laura)->patchJson('/api/me', [
            'nombre' => 'Laura',
            'email' => 'otra@empresa-ejemplo.com',
            'sector_id' => $direccion,
            'jefe_id' => $jefe->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'sector_id', 'jefe_id']);

        $this->assertDatabaseHas('users', ['id' => $laura->id, 'email' => 'laura@empresa-ejemplo.com', 'sector_id' => $laura->sector_id, 'jefe_id' => null]);
    }

    public function test_cambio_en_la_matriz_vale_desde_el_proximo_pedido(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos');
        $this->actingAs($laura)->getJson('/api/me')->assertJsonPath('data.permisos.proveedores.nivel', 'sin_acceso');

        $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [[
            'sector_id' => $laura->sector_id, 'funcion' => 'proveedores', 'nivel' => 'ver', 'alcance' => 'todas',
        ]]])->assertOk();

        $this->actingAs($laura)->getJson('/api/me')->assertJsonPath('data.permisos.proveedores.nivel', 'ver');
    }
}
