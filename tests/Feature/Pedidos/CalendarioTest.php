<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-044 · F-11 (W-03): calendario de mes o semana, Mis tareas o Mi gente, filtro por obra. Hoy: lunes 19-oct. */
final class CalendarioTest extends PedidosTestCase
{
    private const OCTUBRE = '/api/pedidos/calendario?desde=2026-09-28&hasta=2026-11-01';

    /**
     * @return list<int>
     */
    private function ids(User $user, string $extra = ''): array
    {
        $ids = $this->actingAs($user)->getJson(self::OCTUBRE.$extra)->assertOk()->json('data.*.id');
        $this->assertIsArray($ids);

        return array_values(array_map(static fn (mixed $id): int => is_int($id) ? $id : 0, $ids));
    }

    public function test_calendario_ubica_cada_pedido_en_su_entrega(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pedido = $this->crear($laura, $carla, $this->obra(), ['fecha_entrega' => '2026-10-23']);
        $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-11-10']);
        $this->crear($laura, $laura, null, ['fecha_entrega' => '2026-10-21']);

        $this->actingAs($carla)->getJson(self::OCTUBRE)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pedido->id)
            ->assertJsonPath('data.0.fecha_entrega', '2026-10-23')
            ->assertJsonPath('data.0.prioridad', 'prioritario')
            ->assertJsonPath('data.0.estado', 'no_iniciada')
            ->assertJsonPath('data.0.obra.nombre_trabajo', 'Reparación de losa tanque B')
            ->assertJsonPath('data.0.propietario.nombre', 'Carla');
    }

    public function test_calendario_incluye_el_periodo_como_barra(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $alojamiento = $this->crear($laura, $carla, null, [
            'tarea' => 'Alojamiento 6 personas', 'fecha_entrega' => '2026-09-20', 'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-11-06',
        ]);

        $this->actingAs($carla)->getJson(self::OCTUBRE)
            ->assertJsonPath('data.0.id', $alojamiento->id)
            ->assertJsonPath('data.0.periodo_inicio', '2026-10-26')
            ->assertJsonPath('data.0.periodo_fin', '2026-11-06');
        $this->assertSame([], $this->idsEn($carla, '2026-11-09', '2026-11-15'));
    }

    public function test_calendario_trae_completados_para_tacharlos(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $completado = $this->crear($laura, $carla);
        $this->accion($carla, $completado, 'completar', ['comentario' => 'Listo.'])->assertOk();

        $this->actingAs($carla)->getJson(self::OCTUBRE)->assertJsonPath('data.0.id', $completado->id)->assertJsonPath('data.0.estado', 'completada');
    }

    public function test_calendario_mis_tareas_incluye_los_de_mi_sector_sin_propietario(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $pablo = $this->persona('Pablo', 'Compras');
        $enSector = $this->crear($laura, $pablo);
        $this->accion($pablo, $enSector, 'reasignar', ['sector_id' => $pablo->sector_id])->assertOk();

        $this->assertSame([$enSector->id], $this->ids($carla));
    }

    public function test_calendario_mi_gente_solo_con_gente_a_cargo_y_sin_incluirme(): void
    {
        $diego = $this->diego();
        $carla = $this->persona('Carla', 'Compras', ['jefe_id' => $diego->id]);
        $laura = $this->laura();
        $deCarla = $this->crear($laura, $carla);
        $this->crear($laura, $diego);
        $this->crear($laura, $this->sofia());

        $this->assertSame([$deCarla->id], $this->ids($diego, '&de=mi_gente'));
        $this->actingAs($laura)->getJson(self::OCTUBRE.'&de=mi_gente')->assertForbidden();
    }

    public function test_calendario_filtra_por_obra_y_sin_obra(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $deObra = $this->crear($laura, $carla, $obra);
        $sinObra = $this->crear($laura, $carla);

        $this->assertSame([$deObra->id], $this->ids($carla, '&obra_id='.$obra->id));
        $this->assertSame([$sinObra->id], $this->ids($carla, '&obra_id=sin_obra'));
    }

    public function test_calendario_con_alcance_propias_muestra_lo_suyo_y_lo_de_su_gente(): void
    {
        $this->celda('Jefatura de Administración', 'pedidos', 'ver', 'propias');
        $diego = $this->diego();
        $carla = $this->persona('Carla', 'Compras', ['jefe_id' => $diego->id]);
        $laura = $this->laura();
        $suyo = $this->crear($laura, $diego);
        $deSuGente = $this->crear($laura, $carla);
        $this->crear($laura, $this->sofia());

        $this->assertSame([$suyo->id], $this->ids($diego, '&de=mias'));
        $this->assertSame([$deSuGente->id], $this->ids($diego, '&de=mi_gente'));
    }

    public function test_calendario_exige_pedidos_ver(): void
    {
        $this->celda('Calidad', 'pedidos', 'sin_acceso');

        $this->actingAs($this->sofia())->getJson(self::OCTUBRE)->assertForbidden();
    }

    public function test_calendario_valida_el_rango(): void
    {
        $laura = $this->laura();

        $this->actingAs($laura)->getJson('/api/pedidos/calendario')->assertUnprocessable()->assertJsonValidationErrors(['desde', 'hasta'], 'errors');
        $this->actingAs($laura)->getJson('/api/pedidos/calendario?desde=2026-10-01&hasta=2026-12-31')
            ->assertUnprocessable()
            ->assertJsonPath('errors.hasta.0', 'Elegí un rango de hasta 62 días.');
        $this->actingAs($laura)->getJson('/api/pedidos/calendario?desde=2026-10-31&hasta=2026-10-01')->assertUnprocessable();
        $this->actingAs($laura)->getJson(self::OCTUBRE.'&de=todos')->assertUnprocessable()->assertJsonValidationErrors(['de'], 'errors');
    }

    public function test_el_calendario_no_hace_una_consulta_por_pedido(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $this->crear($laura, $carla, $this->obra());
        $this->actingAs($carla)->getJson(self::OCTUBRE)->assertOk();
        $una = $this->consultasDe(fn () => $this->actingAs($carla)->getJson(self::OCTUBRE)->assertOk());

        foreach (['Piso industrial nave 3', 'Impermeabilización cubierta'] as $obra) {
            $this->crear($laura, $carla, $this->obra(nombre: $obra));
        }

        $this->assertLessThanOrEqual($una, $this->consultasDe(fn () => $this->actingAs($carla)->getJson(self::OCTUBRE)->assertJsonCount(3, 'data')));
    }

    /**
     * @return list<int>
     */
    private function idsEn(User $user, string $desde, string $hasta): array
    {
        $ids = $this->actingAs($user)->getJson("/api/pedidos/calendario?desde={$desde}&hasta={$hasta}")->assertOk()->json('data.*.id');
        $this->assertIsArray($ids);

        return array_values(array_map(static fn (mixed $id): int => is_int($id) ? $id : 0, $ids));
    }
}
