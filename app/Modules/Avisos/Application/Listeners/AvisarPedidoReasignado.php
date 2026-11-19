<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoReasignado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `reasignado`: al nuevo propietario o a las personas del sector, con el comentario si lo hay (RN-AVI-09). */
final class AvisarPedidoReasignado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoReasignado $evento): void
    {
        $this->avisar->handle(
            $evento,
            static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->reasignado($evento->participantes, $evento->actorId, $padron),
            $evento->comentario,
        );
    }
}
