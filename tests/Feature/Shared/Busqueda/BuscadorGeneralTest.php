<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Busqueda;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Shared\Busqueda\FuentesBusqueda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ObrasEnBuscadorFalsas;
use Tests\TestCase;

/**
 * F-03 (parte semana 1) · W-18 · HU-022: un solo buscador, agrupado por tipo y según permisos. El grupo
 * Obras es un doble (`ObrasEnBuscadorFalsas`) hasta que Obras registre el suyo.
 */
final class BuscadorGeneralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(FuentesBusqueda::class)->registrar('obras', ObrasEnBuscadorFalsas::class);
        Proveedor::factory()->llamado('Corralón Centro')->deRubro('materiales')->create();
        Proveedor::factory()->llamado('Tanques Ejemplo SRL')->create();
    }

    public function test_agrupa_obras_y_proveedores_con_su_cantidad(): void
    {
        $response = $this->actingAs($this->usuarioDe('Dirección'))->getJson('/api/buscar?q=tanque')->assertOk();

        $response->assertJsonPath('data.grupos.0.tipo', 'obras')
            ->assertJsonPath('data.grupos.0.cantidad', 1)
            ->assertJsonPath('data.grupos.0.resultados.0.numero', '1-087-2026')
            ->assertJsonPath('data.grupos.1.tipo', 'proveedores')
            ->assertJsonPath('data.grupos.1.cantidad', 1)
            ->assertJsonPath('data.grupos.1.resultados.0', [
                'id' => Proveedor::query()->where('nombre', 'Tanques Ejemplo SRL')->valueOrFail('id'),
                'nombre' => 'Tanques Ejemplo SRL',
                'rubro_habitual' => null,
                'es_generico' => false,
            ]);
    }

    public function test_buscar_parte_del_numero_encuentra_la_obra(): void
    {
        $this->actingAs($this->usuarioDe('Dirección'))->getJson('/api/buscar?q=087')
            ->assertOk()
            ->assertJsonPath('data.grupos.0.resultados.0.numero', '1-087-2026')
            ->assertJsonPath('data.grupos.1.cantidad', 0)
            ->assertJsonPath('data.grupos.1.resultados', []);
    }

    public function test_buscar_sin_tildes_encuentra_proveedor_con_tildes(): void
    {
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/buscar?q=CORRALON')
            ->assertOk()
            ->assertJsonPath('data.grupos.1.resultados.0.nombre', 'Corralón Centro')
            ->assertJsonPath('data.grupos.1.resultados.0.rubro_habitual.clave', 'materiales');
    }

    public function test_buscador_no_devuelve_grupo_de_modulo_sin_acceso(): void
    {
        // Ingeniería: Obras = editar, Proveedores sin acceso.
        $response = $this->actingAs($this->usuarioDe('Ingeniería de proyectos'))->getJson('/api/buscar?q=tanque')->assertOk();

        $this->assertSame(['obras'], array_column((array) $response->json('data.grupos'), 'tipo'));
        $this->assertStringNotContainsString('Tanques Ejemplo', (string) $response->getContent());
    }

    public function test_sin_acceso_a_ningun_grupo_no_devuelve_nada(): void
    {
        // Un sector nuevo de fábrica no tiene celdas: sin acceso a nada.
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/buscar?q=tanque')->assertOk()->assertJsonPath('data.grupos', []);
    }

    public function test_con_menos_de_dos_caracteres_no_busca_todavia(): void
    {
        $user = $this->usuarioDe('Dirección');

        $this->actingAs($user)->getJson('/api/buscar?q=t')->assertOk()->assertJsonPath('data.grupos', []);
        $this->actingAs($user)->getJson('/api/buscar?q=%20%20')->assertOk()->assertJsonPath('data.grupos', []);
        $this->actingAs($user)->getJson('/api/buscar')->assertOk()->assertJsonPath('data.grupos', []);
    }

    public function test_cantidad_cuenta_todos_aunque_muestre_los_primeros(): void
    {
        Proveedor::factory()->count(12)->sequence(fn ($secuencia): array => ['nombre' => 'Tanque Proveedor '.$secuencia->index])->create();

        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/buscar?q=tanque')
            ->assertOk()
            ->assertJsonPath('data.grupos.1.cantidad', 13)
            ->assertJsonCount(10, 'data.grupos.1.resultados');
    }

    public function test_texto_demasiado_largo_es_rechazado(): void
    {
        $this->actingAs($this->usuarioDe('Compras'))->getJson('/api/buscar?q='.Str::repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    private function usuarioDe(string $sector): User
    {
        return User::factory()->enSector($sector)->create();
    }
}
