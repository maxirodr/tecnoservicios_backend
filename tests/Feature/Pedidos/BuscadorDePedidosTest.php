<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-057 · F-03 (W-18) · RN-PED-51 · DU-P15: grupo Pedidos del buscador general. */
final class BuscadorDePedidosTest extends PedidosTestCase
{
    /**
     * @return array<string, mixed>|null
     */
    private function grupo(User $user, string $texto): ?array
    {
        $grupos = (array) $this->actingAs($user)->getJson('/api/buscar?q='.urlencode($texto))->assertOk()->json('data.grupos');
        $grupo = collect($grupos)->firstWhere('tipo', 'pedidos');

        return is_array($grupo) ? $grupo : null;
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function buscar(User $user, string $texto): TestResponse
    {
        return $this->actingAs($user)->getJson('/api/buscar?q='.urlencode($texto))->assertOk();
    }

    public function test_buscador_encuentra_pedidos_por_tarea_con_quien_y_aclaraciones(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $pedido = $this->crear($laura, $carla, $obra);
        $porConQuien = $this->crear($laura, $carla, null, ['tarea' => 'Bolsas de cemento', 'con_quien' => 'Corralón Centro']);
        $porAclaraciones = $this->crear($laura, $carla, null, ['tarea' => 'Envío de EPP a obra', 'con_quien' => null, 'aclaraciones' => 'Botines talle 42 para la cuadrilla']);
        $this->actingAs($carla)->postJson("/api/pedidos/{$pedido->id}/comentarios", ['texto' => 'Pedido hecho a Ferretería Sur'])->assertCreated();

        $this->buscar($laura, 'andamio')
            ->assertJsonPath('data.grupos.1.tipo', 'pedidos')
            ->assertJsonPath('data.grupos.1.cantidad', 1)
            ->assertJsonPath('data.grupos.1.resultados.0', [
                'id' => $pedido->id,
                'tarea' => 'Alquiler de andamio para el sector de bombas',
                'obra' => ['id' => $obra->id, 'nombre_trabajo' => 'Reparación de losa tanque B'],
                'propietario' => ['id' => $carla->id, 'nombre' => 'Carla'],
                'sector' => ['id' => $carla->sector_id, 'nombre' => 'Compras'],
                'estado' => 'no_iniciada',
            ]);
        $this->assertSame($porConQuien->id, $this->grupo($laura, 'corralon')['resultados'][0]['id'] ?? null);
        $this->assertSame($porAclaraciones->id, $this->grupo($laura, 'CUADRILLA')['resultados'][0]['id'] ?? null);
        $this->assertSame(0, $this->grupo($laura, 'Ferretería Sur')['cantidad'] ?? null);
    }

    public function test_buscador_encuentra_completados_y_de_obras_terminadas_abiertos_primero(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $completado = $this->crear($laura, $carla, $this->obra(ObraEstado::Terminada, 'Impermeabilización cubierta'), ['tarea' => 'Curso de trabajo en altura']);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();
        $abierto = $this->crear($laura, $carla, null, ['tarea' => 'Curso de trabajo en altura · obras Piso nave 3 y Losa tanque B']);

        $grupo = $this->grupo($laura, 'curso de trabajo');

        $this->assertSame([$abierto->id, $completado->id], array_column($grupo['resultados'] ?? [], 'id'));
        $this->assertSame([$abierto->id], array_column($this->grupo($laura, 'nave 3')['resultados'] ?? [], 'id'));
    }

    public function test_buscador_de_pedidos_respeta_el_alcance(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $sofia = $this->sofia();
        $laura = $this->laura();
        $suyo = $this->crear($laura, $sofia);
        $this->crear($laura, $this->carla());

        $grupo = $this->grupo($sofia, 'andamio');

        $this->assertSame(1, $grupo['cantidad'] ?? null);
        $this->assertSame($suyo->id, $grupo['resultados'][0]['id'] ?? null);
    }

    public function test_grupo_pedidos_no_viaja_sin_permiso(): void
    {
        $sofia = $this->sofia();
        $this->crear($this->laura(), $sofia);
        $this->celda('Calidad', 'pedidos', 'sin_acceso');

        $response = $this->buscar($sofia, 'andamio');

        $this->assertNotContains('pedidos', array_column((array) $response->json('data.grupos'), 'tipo'));
        $this->assertStringNotContainsString('andamio', (string) $response->getContent());
    }

    public function test_muchos_resultados_trae_los_primeros_10_y_la_cantidad(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();

        foreach (range(1, 11) as $numero) {
            $this->crear($laura, $carla, null, ['tarea' => "Solicitud de materiales N.º {$numero}"]);
        }

        $grupo = $this->grupo($laura, 'solicitud de materiales');

        $this->assertSame(11, $grupo['cantidad'] ?? null);
        $this->assertCount(10, $grupo['resultados'] ?? []);
    }

    public function test_comodines_se_buscan_como_texto(): void
    {
        $laura = $this->laura();
        $this->crear($laura, $this->carla());

        $this->assertSame(0, $this->grupo($laura, '%%')['cantidad'] ?? null);
        $this->assertSame(0, $this->grupo($laura, '__')['cantidad'] ?? null);
    }
}
