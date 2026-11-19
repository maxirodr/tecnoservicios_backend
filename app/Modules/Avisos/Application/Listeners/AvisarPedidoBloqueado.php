<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoBloqueado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `bloqueado`: a quien lo pidió y a los seguidores, con el motivo (RN-AVI-10). */
final class AvisarPedidoBloqueado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoBloqueado $evento): void
    {
        $this->avisar->handle(
            $evento,
            static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->bloqueado($evento->participantes, $evento->actorId, $padron),
            $evento->motivo,
        );
    }
}
