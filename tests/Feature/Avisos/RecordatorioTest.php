<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Contracts\PedidoParaRecordar;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event as TareaProgramada;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Tests\Support\PedidosParaRecordarFalsos;

/**
 * F-37 · HU-048 (RN-AVI-16…18; DU-P21 desde las 8:00, DU-Q01 días corridos). Pedidos se reemplaza por un
 * fake del contrato `PedidosParaRecordar` que filtra por la fecha pedida, como hará la implementación real.
 */
final class RecordatorioTest extends AvisosTestCase
{
    private PedidosParaRecordarFalsos $pedidos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pedidos = new PedidosParaRecordarFalsos;
        $this->app->instance(PedidosParaRecordar::class, $this->pedidos);
    }

    public function test_recordatorio_avisa_al_propietario_un_dia_antes(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-24');

        $this->correrA('2026-11-23 09:00');

        $avisos = $this->avisosDe($carla);
        $this->assertCount(1, $avisos);
        $this->assertSame('vence_manana', $avisos[0]->tipo->value);
        $this->assertSame('Vence mañana: Informe fotográfico semanal · Piso industrial nave 3', $avisos[0]->dato('texto'));
        $this->assertNull($avisos[0]->actor_id);
        $this->assertSame("recordatorio:1:2026-11-24:{$carla->id}", $avisos[0]->dedupe_key);
    }

    public function test_recordatorio_no_sale_antes_de_las_8(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-24');

        $this->correrA('2026-11-23 07:59', 'Todavía no son las 8:00');
        $this->assertSame(0, Aviso::query()->count());

        $this->correrA('2026-11-23 08:00', 'Recordatorios creados: 1.');
        $this->assertSame(1, Aviso::query()->count());
    }

    public function test_recordatorio_no_se_repite_en_la_siguiente_corrida(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-24');

        $this->correrA('2026-11-23 09:00');
        $this->correrA('2026-11-23 10:00', 'Recordatorios creados: 0.');

        $this->assertCount(1, $this->avisosDe($carla));
    }

    public function test_pedido_cargado_despues_de_las_8_se_recuerda_en_la_corrida_siguiente(): void
    {
        $carla = $this->persona('Carla');
        $this->correrA('2026-11-23 08:00');

        $this->abierto(1, $carla, '2026-11-24');
        $this->correrA('2026-11-23 16:00');

        $this->assertCount(1, $this->avisosDe($carla));
    }

    public function test_recordatorio_sale_de_nuevo_si_cambia_la_entrega(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-24');
        $this->correrA('2026-11-23 09:00');

        $this->pedidos->abiertos = [];
        $this->abierto(1, $carla, '2026-11-25');
        $this->correrA('2026-11-24 08:00');

        $this->assertSame(
            ["recordatorio:1:2026-11-24:{$carla->id}", "recordatorio:1:2026-11-25:{$carla->id}"],
            Aviso::query()->orderBy('id')->pluck('dedupe_key')->all(),
        );
    }

    public function test_recordatorio_en_el_sector_avisa_a_todas_las_personas_activas(): void
    {
        $carla = $this->persona('Carla', 'Compras');
        $diego = $this->persona('Diego', 'Compras');
        $this->persona('Marta', 'Compras', ['activo' => false]);
        $this->pedidos->abiertos[] = new PedidoParaRecordar(2, null, $this->sectorId('Compras'), 'Compra de discos', null, '2026-11-24');

        $this->correrA('2026-11-23 09:00');

        $this->assertSame([$carla->id, $diego->id], Aviso::query()->orderBy('user_id')->pluck('user_id')->all());
        $this->assertSame('Vence mañana: Compra de discos · Servicios generales', $this->avisosDe($diego)[0]->dato('texto'));
    }

    public function test_recordatorio_ignora_los_que_vencen_hoy_o_pasado_manana(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-23');
        $this->abierto(2, $carla, '2026-11-25');

        $this->correrA('2026-11-23 09:00');

        $this->assertSame(0, Aviso::query()->count());
    }

    public function test_recordatorio_nunca_sale_por_mail(): void
    {
        Mail::fake();
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-24');

        $this->correrA('2026-11-23 09:00');

        $this->assertSame(1, Aviso::query()->count());
        Mail::assertNothingQueued();
    }

    public function test_recordatorio_usa_dias_corridos_en_hora_argentina(): void
    {
        $carla = $this->persona('Carla');
        $this->abierto(1, $carla, '2026-11-23');

        // Domingo 22 a las 23:30 de Argentina ya es lunes 23 en UTC: "mañana" es el lunes, no el martes.
        $this->correrA('2026-11-22 23:30');

        $this->assertCount(1, $this->avisosDe($carla), 'Un pedido para el lunes se recuerda el domingo (DU-Q01).');
    }

    public function test_el_recordatorio_esta_programado_cada_hora_en_el_scheduler(): void
    {
        $tareas = array_values(array_filter(
            app(Schedule::class)->events(),
            static fn (TareaProgramada $tarea): bool => str_contains((string) $tarea->command, 'avisos:recordatorios'),
        ));

        $this->assertCount(1, $tareas);
        $this->assertSame('0 * * * *', $tareas[0]->expression);
    }

    private function abierto(int $pedidoId, User $propietario, string $entrega): void
    {
        $this->pedidos->abiertos[] = new PedidoParaRecordar($pedidoId, $propietario->id, $propietario->sector_id, 'Informe fotográfico semanal', 'Piso industrial nave 3', $entrega);
    }

    /** Corre el comando como lo haría el scheduler a esa hora de Argentina. */
    private function correrA(string $horaArgentina, ?string $salida = null): void
    {
        $this->travelTo(CarbonImmutable::parse($horaArgentina, config()->string('app.zona_empresa')));

        $pendiente = $this->command('avisos:recordatorios')->assertSuccessful();
        if ($salida !== null) {
            $pendiente->expectsOutputToContain($salida);
        }
        $pendiente->run();
    }
}
