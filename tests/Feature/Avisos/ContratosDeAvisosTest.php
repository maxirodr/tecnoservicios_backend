<?php

declare(strict_types=1);

namespace Tests\Feature\Avisos;

use App\Modules\Avisos\Contracts\AvisosDelPedido;
use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;

/** RN-AVI-28: lo que Avisos le da a Pedidos para el recorrido (W-09) y el "Se le avisó a Carla" (W-43). */
final class ContratosDeAvisosTest extends AvisosTestCase
{
    public function test_avisos_del_pedido_agrupa_por_evento_para_el_recorrido(): void
    {
        $hernan = $this->persona('Hernán', 'Jefatura de Proyectos');
        $laura = $this->persona('Laura', 'Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $carla = $this->persona('Carla', 'Compras');
        $sofia = $this->persona('Sofía');
        event(new PedidoAsignado(88, $laura->id, 501, $this->participantes($laura, $carla), $this->foto()));
        event(new PedidoCompletado(88, $carla->id, 502, $this->participantes($laura, $carla, [$sofia->id]), $this->foto(), 'Listo.'));
        event(new PedidoAsignado(99, $laura->id, 503, $this->participantes($laura, $carla), $this->foto()));

        $enviados = app(AvisosDelPedido::class)->enviadosDe(88);

        $this->assertSame(['asignado', 'completado', 'completado_gente'], array_map(static fn ($e): string => $e->tipo, $enviados));
        $this->assertSame([['id' => $carla->id, 'nombre' => 'Carla']], $enviados[0]->destinatarios);
        $this->assertSame([['id' => $laura->id, 'nombre' => 'Laura'], ['id' => $sofia->id, 'nombre' => 'Sofía']], $enviados[1]->destinatarios);
        $this->assertSame([['id' => $hernan->id, 'nombre' => 'Hernán']], $enviados[2]->destinatarios);
    }

    public function test_destinatarios_de_asignacion_coinciden_con_los_avisos_creados(): void
    {
        $carla = $this->persona('Carla', 'Compras');
        $diego = $this->persona('Diego', 'Compras');
        $this->persona('Marta', 'Compras', ['activo' => false]);
        $participantes = $this->participantes($carla, null, [], $this->sectorId('Compras'));

        $destinatarios = app(DestinatariosDeAviso::class)->deAsignacion($participantes, $carla->id);
        event(new PedidoAsignado(88, $carla->id, 501, $participantes, $this->foto()));

        $this->assertSame([$diego->id], array_map(static fn (User $u): int => $u->id, $destinatarios));
        $this->assertSame([$diego->id], array_column(app(AvisosDelPedido::class)->enviadosDe(88)[0]->destinatarios, 'id'));
    }

    public function test_nadie_recibe_la_asignacion_si_se_la_carga_a_si_mismo(): void
    {
        $carla = $this->persona('Carla');

        $this->assertSame([], app(DestinatariosDeAviso::class)->deAsignacion($this->participantes($carla, $carla), $carla->id));
    }
}
