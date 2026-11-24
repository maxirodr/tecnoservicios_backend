<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use Illuminate\Support\Str;
use Tests\Feature\Rendicion\Support\CasoDeReferencia;
use Tests\Feature\Rendicion\Support\RendicionTestCase;

/**
 * RN-REN-55…58 (rendicion.md §7): cada endpoint con su caso permitido, denegado (403) e IDOR (404 fuera de
 * alcance, sin confirmar que la obra existe). Laura (Ingeniería, V·P) es responsable de la Ampliación; la
 * Obra de control es de Hernán.
 */
final class PermisosDeRendicionTest extends RendicionTestCase
{
    use CasoDeReferencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cargarCasoDeReferencia();
    }

    public function test_rendicion_de_la_obra_permitida_denegada_y_fuera_de_alcance(): void
    {
        foreach (['', '/rubros/materiales', '/por-proveedor'] as $ruta) {
            $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->ampliacion->id}/rendicion{$ruta}")->assertOk();
            $this->actingAs($this->calidad())->getJson("/api/obras/{$this->ampliacion->id}/rendicion{$ruta}")
                ->assertForbidden()->assertJsonPath('message', 'No tenés acceso a la rendición.');
            $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->control->id}/rendicion{$ruta}")
                ->assertNotFound()->assertJsonPath('message', 'No encontramos esta obra.');
            $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/999999/rendicion{$ruta}")
                ->assertNotFound()->assertJsonPath('message', 'No encontramos esta obra.');
        }
    }

    public function test_sin_sesion_devuelve_401(): void
    {
        $this->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertUnauthorized();
        $this->getJson('/api/rendiciones')->assertUnauthorized();
    }

    public function test_rendiciones_en_curso_sin_rendicion_devuelve_403(): void
    {
        $this->actingAs($this->calidad())->getJson('/api/rendiciones')->assertForbidden()->assertJsonPath('message', 'No tenés acceso a la rendición.');
        $this->actingAs($this->operaciones())->getJson('/api/rendiciones')->assertOk()->assertJsonCount(2, 'data.obras');
    }

    public function test_accesos_de_la_pantalla_segun_la_matriz(): void
    {
        $diego = $this->actingAs($this->diego())->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->json('data.puede');
        $this->assertSame(array_fill_keys(['ver_monto_obra', 'abrir_gastos', 'ver_todos_los_gastos', 'cargar_sin_documento', 'cargar_periodo', 'ver_control_cantidad', 'ver_presupuesto_rubro'], true), $diego);

        $operaciones = $this->actingAs($this->operaciones())->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertOk();
        $this->assertSame(['ver_monto_obra' => true, 'cargar_sin_documento' => false, 'cargar_periodo' => false], array_intersect_key((array) $operaciones->json('data.puede'), array_flip(['ver_monto_obra', 'cargar_sin_documento', 'cargar_periodo'])));
        $operaciones->assertJsonPath('data.monto.monto_obra', '16300000.00');

        $laura = $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->json('data.puede');
        $this->assertSame([false, true, false], [$laura['ver_monto_obra'], $laura['abrir_gastos'], $laura['cargar_periodo']]);
    }

    public function test_ver_presupuestado_no_pide_presupuesto_rubro(): void
    {
        $this->celdaSinAcceso('Ingeniería de proyectos', 'presupuesto_rubro');

        $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->ampliacion->id}/rendicion")
            ->assertOk()
            ->assertJsonPath('data.rubros.0.presupuestado', '5000000.00')
            ->assertJsonPath('data.puede.ver_presupuesto_rubro', false);
    }

    public function test_monto_respeta_alcance_propias(): void
    {
        $this->celda('Ingeniería de proyectos', 'monto_obra', 'ver', 'propias');
        $this->celda('Ingeniería de proyectos', 'rendicion', 'ver', 'todas');
        $laura = $this->responsableAmpliacion;

        $this->actingAs($laura)->getJson("/api/obras/{$this->ampliacion->id}/rendicion")->assertJsonPath('data.monto.monto_obra', '16300000.00');
        $this->assertArrayNotHasKey('monto', (array) $this->actingAs($laura)->getJson("/api/obras/{$this->control->id}/rendicion")->assertOk()->json('data'));
    }

    public function test_control_por_cantidad_permitido_denegado_y_fuera_de_alcance(): void
    {
        $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->ampliacion->id}/control-cantidad")->assertOk()->assertJsonPath('data.puede.editar', false);
        $this->actingAs($this->calidad())->getJson("/api/obras/{$this->ampliacion->id}/control-cantidad")->assertForbidden();
        $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->control->id}/control-cantidad")->assertNotFound();
    }

    public function test_control_cantidad_ver_no_edita(): void
    {
        $hernan = $this->hernan();
        $cemento = $this->materiales['cemento'];

        $this->actingAs($hernan)->postJson("/api/obras/{$this->ampliacion->id}/materiales", ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '5'])->assertForbidden();
        $this->actingAs($hernan)->patchJson("/api/materiales/{$cemento->id}", ['cantidad_presupuestada' => '160'])->assertForbidden();
        $this->actingAs($hernan)->deleteJson("/api/materiales/{$this->materiales['malla']->id}")->assertForbidden();
        $this->assertSame('150.000', $cemento->refresh()->cantidad_presupuestada);
    }

    public function test_lista_de_materiales_fuera_de_alcance_devuelve_404(): void
    {
        $this->celda('Compras', 'control_cantidad', 'editar', 'propias');
        $compras = $this->usuarioDe('Compras');

        $this->actingAs($compras)->postJson("/api/obras/{$this->ampliacion->id}/materiales", ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '5'])->assertNotFound();
        $this->actingAs($compras)->patchJson("/api/materiales/{$this->materiales['cemento']->id}", ['cantidad_presupuestada' => '160'])->assertNotFound();
        $this->actingAs($compras)->deleteJson("/api/materiales/{$this->materiales['malla']->id}")->assertNotFound()->assertJsonPath('message', 'No encontramos este material.');
        $this->actingAs($this->diego())->patchJson('/api/materiales/999999', ['unidad' => 'kg'])->assertNotFound();
    }

    public function test_opciones_de_materiales_con_gastos_editar_rendicion_editar_o_control_ver(): void
    {
        $url = "/api/obras/{$this->ampliacion->id}/materiales/opciones";

        $this->actingAs($this->responsableAmpliacion)->getJson($url)->assertOk()->assertJsonCount(5, 'data');
        $this->actingAs($this->diego())->getJson($url)->assertOk();
        $this->actingAs($this->calidad())->getJson($url)->assertForbidden();
        $this->actingAs($this->responsableAmpliacion)->getJson("/api/obras/{$this->control->id}/materiales/opciones")->assertNotFound();

        $this->celda('Calidad', 'gastos', 'editar');
        $this->actingAs($this->calidad())->getJson($url)->assertOk();
    }

    public function test_alta_de_material_con_control_cantidad_editar(): void
    {
        $this->actingAs($this->diego())
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson("/api/obras/{$this->ampliacion->id}/materiales", ['nombre' => 'Arena', 'unidad' => 'm³', 'cantidad_presupuestada' => '5'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Arena');
    }
}
