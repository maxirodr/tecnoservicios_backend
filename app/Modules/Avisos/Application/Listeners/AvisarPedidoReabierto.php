<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoReabierto;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `reabierto`: al propietario (o al que se eligió al reabrir), con el comentario (RN-AVI-13). */
final class AvisarPedidoReabierto implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoReabierto $evento): void
    {
        $this->avisar->handle(
            $evento,
            static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->reabierto($evento->participantes, $evento->actorId, $padron),
            $evento->comentario,
        );
    }
}
