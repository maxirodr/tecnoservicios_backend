<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoTomado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `tomado`: a quien lo pidió y a los seguidores (RN-AVI-10). */
final class AvisarPedidoTomado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoTomado $evento): void
    {
        $this->avisar->handle($evento, static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->tomado($evento->participantes, $evento->actorId, $padron));
    }
}
