<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Avisos\Domain\Models\PreferenciaAviso;
use App\Modules\Avisos\Infrastructure\Mail\AvisoPedidoMail;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;

/** F-09 · F-10 · HU-047 (RN-AVI-19…21): qué sale por mail, por qué cola y con qué. */
final class MailDeAvisosTest extends AvisosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_asignacion_sale_por_mail_por_defecto_por_la_cola_mail(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla', 'Compras', ['email' => 'carla@empresa-ejemplo.com']);

        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));

        Mail::assertQueued(AvisoPedidoMail::class, static fn (AvisoPedidoMail $mail): bool => $mail->hasTo('carla@empresa-ejemplo.com') && $mail->queue === 'mail');
        Mail::assertQueuedCount(1);
    }

    public function test_reasignacion_al_sector_manda_un_mail_a_cada_persona_activa(): void
    {
        $carla = $this->persona('Carla', 'Compras');
        $this->persona('Diego', 'Compras');
        $this->persona('Sofía', 'Compras');
        $this->persona('Marta', 'Compras', ['activo' => false]);

        event(new PedidoReasignado(88, $carla->id, $this->actividad(), $this->participantes($carla, null, [], $this->sectorId('Compras')), $this->foto(), $carla->id, true));

        Mail::assertQueuedCount(2);
    }

    public function test_tomado_no_sale_por_mail_si_no_lo_activo(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');

        event(new PedidoTomado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));

        $this->assertCount(1, $this->avisosDe($laura));
        Mail::assertNothingQueued();
    }

    public function test_tomado_sale_por_mail_si_lo_activo_pero_nunca_al_seguidor(): void
    {
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['email' => 'laura@empresa-ejemplo.com']);
        $carla = $this->persona('Carla');
        $sofia = $this->persona('Sofía');
        foreach ([$laura, $sofia] as $quien) {
            PreferenciaAviso::query()->create(['user_id' => $quien->id, 'tipo' => 'tomado', 'por_mail' => true]);
        }

        event(new PedidoTomado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla, [$sofia->id]), $this->foto()));

        Mail::assertQueuedCount(1);
        Mail::assertQueued(AvisoPedidoMail::class, static fn (AvisoPedidoMail $mail): bool => $mail->hasTo('laura@empresa-ejemplo.com'));
    }

    public function test_aviso_al_superior_nunca_sale_por_mail(): void
    {
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $carla = $this->persona('Carla');
        PreferenciaAviso::query()->create(['user_id' => $laura->id, 'tipo' => 'completado', 'por_mail' => true]);

        event(new PedidoCompletado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto(), 'Listo.'));

        $this->assertCount(1, $this->avisosDe($hernan));
        Mail::assertQueuedCount(1);
        Mail::assertQueued(AvisoPedidoMail::class, static fn (AvisoPedidoMail $mail): bool => $mail->hasTo($laura->email));
    }

    public function test_reintento_no_manda_un_segundo_mail(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        $evento = new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto());

        event($evento);
        event($evento);

        Mail::assertQueuedCount(1);
    }

    public function test_mail_lleva_el_asunto_los_datos_del_pedido_y_el_link(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto('Alquiler de obrador', null, '2026-11-20', 'urgente')));

        $mail = new AvisoPedidoMail((int) Aviso::query()->value('id'));

        $mail->assertHasSubject('Te asignaron una tarea: Alquiler de obrador');
        $mail->assertSeeInHtml('Servicios generales');
        $mail->assertSeeInHtml('20/11/2026');
        $mail->assertSeeInHtml('Urgente');
        $mail->assertSeeInHtml('Laura');
        $mail->assertSeeInHtml(config()->string('identidad.frontend_url').'/pedidos/88', false);
        $mail->assertSeeInHtml('Podés elegir qué avisos te llegan por mail en Mi perfil.');
    }

    public function test_mail_no_sale_si_el_usuario_se_desactivo(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        event(new PedidoAsignado(88, $laura->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));
        $carla->update(['activo' => false]);

        $enviados = $this->enviar(new AvisoPedidoMail((int) Aviso::query()->value('id')));

        $this->assertSame(0, $enviados);
        $this->assertCount(1, $this->avisosDe($carla), 'El aviso queda.');
    }

    public function test_mail_no_sale_si_lo_desactivo_antes_del_envio(): void
    {
        $laura = $this->persona('Laura');
        $carla = $this->persona('Carla');
        PreferenciaAviso::query()->create(['user_id' => $laura->id, 'tipo' => 'tomado', 'por_mail' => true]);
        event(new PedidoTomado(88, $carla->id, $this->actividad(), $this->participantes($laura, $carla), $this->foto()));
        $mail = new AvisoPedidoMail((int) Aviso::query()->value('id'));

        $this->assertSame(1, $this->enviar($mail));

        PreferenciaAviso::query()->where('user_id', $laura->id)->update(['por_mail' => false]);
        $this->assertSame(0, $this->enviar(new AvisoPedidoMail((int) Aviso::query()->value('id'))));
    }

    /** Lo manda de verdad con el mailer `array` (como el worker de la cola `mail`) y devuelve cuántos salieron. */
    private function enviar(AvisoPedidoMail $mail): int
    {
        $mailer = (new MailManager($this->app))->mailer('array');
        $this->assertInstanceOf(Mailer::class, $mailer);
        $transport = $mailer->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $transport->flush();

        $mail->to('destino@empresa-ejemplo.com')->send($mailer);

        return $transport->messages()->count();
    }
}
