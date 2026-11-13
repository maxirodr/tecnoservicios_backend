<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Ports\PedidoAbiertoResumen;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Events\ObraEstadoCambiado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Actividad\Actividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Obras\Support\ObrasTestCase;
use Tests\Feature\Obras\Support\PuertosFalsos;

/** HU-017 · F-21 · F-22 (W-22): estados con el patrón State y quién los cambia. */
final class EstadoObraTest extends ObrasTestCase
{
    public function test_confirmada_pasa_a_en_ejecucion_y_cambia_de_pestana(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);

        $this->cambiar($obra, 'en_ejecucion', $laura)->assertOk()->assertJsonPath('data.estado', 'en_ejecucion');

        $this->actingAs($laura)->getJson('/api/obras')->assertJsonPath('data.0.id', $obra->id);
    }

    public function test_en_ejecucion_pasa_a_terminada_y_guarda_quien_y_cuando(): void
    {
        Event::fake([ObraEstadoCambiado::class]);
        $diego = $this->diego();
        $obra = $this->obraDe($this->laura(), ObraEstado::EnEjecucion);

        $this->cambiar($obra, 'terminada', $diego)
            ->assertOk()
            ->assertJsonPath('data.terminada_por.nombre', 'Diego');

        $obra->refresh();
        $this->assertSame(ObraEstado::Terminada, $obra->estado);
        $this->assertNotNull($obra->terminada_at);
        $actividad = Actividad::query()->where('accion', 'estado_cambiado')->sole();
        $this->assertSame(['estado' => ['antes' => 'en_ejecucion', 'despues' => 'terminada']], $actividad->cambios);
        $this->assertSame($diego->id, $actividad->user_id);
        Event::assertDispatched(ObraEstadoCambiado::class, fn (ObraEstadoCambiado $e): bool => $e->obraId === $obra->id && $e->a === ObraEstado::Terminada);
    }

    public function test_confirmada_no_salta_a_terminada(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);

        $this->cambiar($obra, 'terminada', $laura)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estado' => 'De Confirmada se pasa primero a En ejecución.']);
        $this->assertSame(ObraEstado::Confirmada, $obra->refresh()->estado);
    }

    public function test_terminada_vuelve_a_en_ejecucion_y_limpia_terminada_at(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura, ObraEstado::Terminada);

        $this->cambiar($obra, 'en_ejecucion', $laura)->assertOk()->assertJsonPath('data.terminada_at', null)->assertJsonPath('data.terminada_por', null);
    }

    public function test_en_ejecucion_vuelve_a_confirmada_y_queda_en_el_historial(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($this->laura(), ObraEstado::EnEjecucion);

        $this->cambiar($obra, 'confirmada', $diego)->assertOk();

        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', 'Cambió el estado: En ejecución → Confirmada');
    }

    public function test_terminada_no_vuelve_directo_a_confirmada(): void
    {
        $diego = $this->diego();
        $obra = $this->obraDe($this->laura(), ObraEstado::Terminada);

        $this->cambiar($obra, 'confirmada', $diego)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estado' => 'De Terminada se vuelve primero a En ejecución.']);
    }

    public function test_responsable_y_estado_obra_editar_cambian_y_el_resto_no(): void
    {
        // Caso borde 8: Laura (responsable, Estado = Ver) 200 · Hernán (Estado = Ver, no responsable) 403 · Diego 200.
        $laura = $this->laura();
        $obra = $this->obraDe($laura);

        $this->cambiar($obra, 'en_ejecucion', $laura)->assertOk();
        $this->cambiar($obra, 'terminada', $this->hernan())->assertForbidden();
        $this->cambiar($obra, 'terminada', $this->diego())->assertOk();
    }

    public function test_quien_no_ve_la_obra_recibe_404(): void
    {
        $this->celda('Gerencia de Administración', 'obras', 'ver', 'propias');
        $gerente = $this->usuarioDe('Gerencia de Administración');
        $obra = $this->obraDe($this->laura());

        $this->cambiar($obra, 'en_ejecucion', $gerente)->assertNotFound();
        $this->cambiar($obra, 'en_ejecucion', $this->sinAcceso())->assertForbidden();
    }

    public function test_pedidos_abiertos_vacio_en_semana_1(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura, ObraEstado::EnEjecucion);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertOk()->assertJsonPath('data', []);
        $this->actingAs($laura)->getJson('/api/obras/999999/pedidos-abiertos')->assertNotFound();
    }

    public function test_terminar_con_pedidos_nuevos_no_confirmados_devuelve_409(): void
    {
        $puertos = PuertosFalsos::registrar($this->app);
        $puertos->abiertos = [self::pedido(1), self::pedido(2), self::pedido(3), self::pedido(4)];
        $diego = $this->diego();
        $obra = $this->obraDe($this->laura(), ObraEstado::EnEjecucion);

        $this->actingAs($diego)->getJson("/api/obras/{$obra->id}/pedidos-abiertos")->assertJsonCount(4, 'data')->assertJsonPath('data.0.sector.nombre', 'Compras');

        $this->cambiar($obra, 'terminada', $diego, [1, 2, 3])
            ->assertStatus(409)
            ->assertJsonCount(4, 'errors.pedidos_abiertos');
        $this->assertSame(ObraEstado::EnEjecucion, $obra->refresh()->estado);

        // Con todos vistos no frena aunque sigan abiertos (DU-12).
        $this->cambiar($obra, 'terminada', $diego, [1, 2, 3, 4])->assertOk();
    }

    public function test_cambio_de_estado_con_idempotency_key_no_se_repite(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $clave = (string) Str::uuid();

        $this->actingAs($laura)->withHeader('Idempotency-Key', $clave)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'en_ejecucion'])->assertOk();
        $this->actingAs($laura)->withHeader('Idempotency-Key', $clave)->postJson("/api/obras/{$obra->id}/estado", ['estado' => 'en_ejecucion'])
            ->assertOk()
            ->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame(1, Actividad::query()->where('accion', 'estado_cambiado')->count());
    }

    public function test_estado_invalido_da_422(): void
    {
        $laura = $this->laura();

        $this->cambiar($this->obraDe($laura), 'pausada', $laura)->assertUnprocessable()->assertJsonValidationErrors(['estado']);
    }

    /**
     * @param  list<int>  $confirmados
     * @return TestResponse<JsonResponse>
     */
    private function cambiar(Obra $obra, string $estado, User $user, array $confirmados = []): TestResponse
    {
        return $this->actingAs($user)->postJson("/api/obras/{$obra->id}/estado", ['estado' => $estado, 'confirmo_pedidos_abiertos' => $confirmados]);
    }

    private static function pedido(int $id): PedidoAbiertoResumen
    {
        return new PedidoAbiertoResumen($id, "Tarea de ejemplo {$id}", null, null, 1, 'Compras', 'no_iniciada');
    }
}
