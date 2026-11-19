<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoComentado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `comentado`: a la otra parte (o a las dos, si comenta un tercero) y a los seguidores (RN-AVI-11). */
final class AvisarPedidoComentado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoComentado $evento): void
    {
        $this->avisar->handle(
            $evento,
            static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->comentado($evento->participantes, $evento->actorId, $padron),
            $evento->texto,
        );
    }
}
