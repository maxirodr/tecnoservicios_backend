<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Application\Listeners\AvisarPedidoAsignado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoBloqueado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoComentado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoCompletado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoReabierto;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoReasignado;
use App\Modules\Avisos\Application\Listeners\AvisarPedidoTomado;
use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Events\PedidoBloqueado;
use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use App\Modules\Pedidos\Domain\Events\PedidoReabierto;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

/** F-08 · F-07 · HU-045 · HU-046 (RN-AVI-01…15): los eventos de Pedidos crean los avisos. */
final class AvisosDeEventosTest extends AvisosTestCase
{
    public function test_cada_evento_de_pedidos_tiene_su_listener(): void
    {
        Event::fake();

        foreach ([
            PedidoAsignado::class => AvisarPedidoAsignado::class,
            PedidoReasignado::class => AvisarPedidoReasignado::class,
            PedidoTomado::class => AvisarPedidoTomado::class,
            PedidoBloqueado::class => AvisarPedidoBloqueado::class,
            PedidoComentado::class => AvisarPedidoComentado::class,
            PedidoCompletado::class => AvisarPedidoCompletado::class,
            PedidoReabierto::class => AvisarPedidoReabierto::class,
        ] as $evento => $listener) {
            Event::assertListening($evento, $listener);
        }
    }

    public function test_listener_corre_en_la_cola_default_con_tres_intentos(): void
    {
        Queue::fake();
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));

        Queue::assertPushedOn('default', CallQueuedListener::class, static fn (CallQueuedListener $job): bool => $job->class === AvisarPedidoAsignado::class && $job->tries === 3);
    }

    public function test_asignacion_crea_aviso_al_propietario_con_el_texto_del_momento(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoAsignado(88, $laura->id, 501, $this->participantes($laura, $carla), $this->foto('Alquiler de andamio para el sector de bombas', 'Reparación de losa tanque B')));

        $avisos = $this->avisosDe($carla);
        $this->assertCount(1, $avisos);
        $this->assertSame('asignado', $avisos[0]->tipo->value);
        $this->assertSame("asignado:501:{$carla->id}", $avisos[0]->dedupe_key);
        $this->assertSame('Laura te asignó Alquiler de andamio para el sector de bombas · Reparación de losa tanque B', $avisos[0]->dato('texto'));
        $this->assertSame($laura->id, $avisos[0]->actor_id);
        $this->assertSame([], $this->avisosDe($laura));
    }

    public function test_aviso_se_crea_despues_del_commit(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        try {
            DB::transaction(function () use ($laura, $carla): void {
                event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));
                throw new RuntimeException('La acción falla y se deshace.');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Aviso::query()->count());
    }

    public function test_reintento_del_listener_no_duplica_por_dedupe_key(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $evento = new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto());

        app(AvisarPedidoAsignado::class)->handle($evento);
        app(AvisarPedidoAsignado::class)->handle($evento);

        $this->assertCount(1, $this->avisosDe($carla));
    }

    public function test_pedido_al_sector_avisa_a_todas_sus_personas_activas_menos_quien_lo_deja(): void
    {
        $carla = $this->persona('Carla', 'Compras');
        $diego = $this->persona('Diego', 'Compras');
        $sofia = $this->persona('Sofía', 'Compras');
        $inactiva = $this->persona('Marta', 'Compras', ['activo' => false]);

        event(new PedidoReasignado(88, $carla->id, $this->actividad(), $this->participantes($carla, null, [], $this->sectorId('Compras')), $this->foto(), $carla->id, true));

        $this->assertSame([], $this->avisosDe($carla));
        $this->assertSame([], $this->avisosDe($inactiva));
        $this->assertCount(1, $this->avisosDe($diego));
        $this->assertSame('Carla dejó en Compras (tu sector) Solicitud de materiales N.º 3: la toma el primero que pueda', $this->avisosDe($sofia)[0]->dato('texto'));
    }

    public function test_tomado_va_al_solicitante_y_a_los_seguidores(): void
    {
        $laura = $this->persona('Laura');
        $diego = $this->persona('Diego');
        $sofia = $this->persona('Sofía');
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $laura->update(['jefe_id' => $hernan->id]);

        event(new PedidoTomado(88, $diego->id, $this->actividad(), $this->participantes($laura, $diego, [$sofia->id, $hernan->id]), $this->foto()));

        $this->assertSame('Diego tomó tu tarea Solicitud de materiales N.º 3 → pasó a En curso', $this->avisosDe($laura)[0]->dato('texto'));
        $this->assertSame('Diego tomó Solicitud de materiales N.º 3 (que seguís) → En curso', $this->avisosDe($sofia)[0]->dato('texto'));
        $this->assertSame('tomado', $this->avisosDe($hernan)[0]->tipo->value, 'El superior solo recibe el completado: acá es seguidor.');
    }

    public function test_bloqueado_lleva_el_motivo(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoBloqueado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto('Envío de EPP a obra'), 'Falta el talle de botines de 3 personas de la cuadrilla.'));

        $this->assertSame("Carla bloqueó tu tarea Envío de EPP a obra: 'Falta el talle de botines de 3 personas de la cuadrilla.'", $this->avisosDe($laura)[0]->dato('texto'));
    }

    public function test_comentario_de_un_tercero_avisa_a_las_dos_partes_y_no_al_autor(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $sofia = $this->persona('Sofía');

        event(new PedidoComentado(88, $sofia->id, $this->actividad(), $this->participantes($laura, $carla, [$sofia->id]), $this->foto('Envío de EPP a obra'), 77, 'Talles: 41, 42 y 44'));

        $this->assertCount(1, $this->avisosDe($laura));
        $this->assertCount(1, $this->avisosDe($carla));
        $this->assertSame([], $this->avisosDe($sofia));
    }

    public function test_completado_va_al_solicitante_y_un_solo_aviso_al_superior_que_tambien_sigue(): void
    {
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $carla = $this->persona('Carla', 'Compras');

        event(new PedidoCompletado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla, [$hernan->id]), $this->foto('Alquiler de obrador'), 'Entregado en obra, adjunto remito', 1));

        $this->assertSame("Carla completó tu tarea Alquiler de obrador: 'Entregado en obra, adjunto remito' · comprobante", $this->avisosDe($laura)[0]->dato('texto'));
        $delSuperior = $this->avisosDe($hernan);
        $this->assertCount(1, $delSuperior);
        $this->assertSame('completado_gente', $delSuperior[0]->tipo->value);
        $this->assertSame("Carla completó Alquiler de obrador, que pidió Laura: 'Entregado en obra, adjunto remito'", $delSuperior[0]->dato('texto'));
    }

    public function test_jefe_que_completa_el_pedido_de_su_gente_no_se_avisa_como_superior(): void
    {
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['jefe_id' => $hernan->id]);

        event(new PedidoCompletado(88, $hernan->id, $this->actividad(), $this->participantes($laura, $hernan), $this->foto(), 'Listo.'));

        $this->assertCount(1, $this->avisosDe($laura));
        $this->assertSame([], $this->avisosDe($hernan));
    }

    public function test_reabierto_va_al_propietario_con_el_comentario(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoReabierto(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto(), 'Faltaron 2 bolsas.'));

        $this->assertSame("Laura reabrió Solicitud de materiales N.º 3 (falta algo): 'Faltaron 2 bolsas.'", $this->avisosDe($carla)[0]->dato('texto'));
        $this->assertSame([], $this->avisosDe($laura));
    }

    public function test_inactivo_no_recibe_avisos(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla', 'Ingeniería de proyectos', ['activo' => false]);

        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));

        $this->assertSame(0, Aviso::query()->count());
    }

    public function test_sin_solicitante_avisa_solo_a_los_seguidores(): void
    {
        $carla = $this->persona('Carla');
        $sofia = $this->persona('Sofía');

        event(new PedidoCompletado(88, $carla->id, $this->actividad(), $this->participantes(null, $carla, [$sofia->id]), $this->foto(), 'Listo.'));

        $this->assertSame([$sofia->id], Aviso::query()->pluck('user_id')->all());
    }

    public function test_aviso_conserva_el_texto_del_momento(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto('Tarea vieja')));

        $laura->update(['nombre' => 'Laura B.']);
        event(new PedidoTomado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto('Tarea nueva')));

        $this->assertStringContainsString('Tarea vieja', (string) $this->avisosDe($carla)[0]->dato('texto'));
        $this->assertSame('Tarea vieja', $this->avisosDe($carla)[0]->dato('tarea'));
    }

    public function test_comentario_largo_se_recorta_en_el_aviso(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoComentado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto(), 77, str_repeat('a', 600)));

        $this->assertSame(str_repeat('a', 200).'…', $this->avisosDe($carla)[0]->dato('extracto'));
    }
}
