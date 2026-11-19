<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoCompletado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `completado` a quien lo pidió y a los seguidores; `completado_gente` a su superior (RN-AVI-12, DU-13). */
final class AvisarPedidoCompletado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoCompletado $evento): void
    {
        $this->avisar->handle(
            $evento,
            static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->completado($evento->participantes, $evento->actorId, $padron),
            $evento->comentario,
            $evento->cantidadComprobantes,
            conSuperior: true,
        );
    }
}
