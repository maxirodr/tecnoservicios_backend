<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

use App\Modules\Avisos\Application\AvisarEventoDePedido;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use Illuminate\Contracts\Queue\ShouldQueue;

/** `asignado`: al propietario o a las personas del sector, en el sistema y por mail (RN-AVI-08). */
final class AvisarPedidoAsignado implements ShouldQueue
{
    use EnColaDeAvisos;

    public function __construct(private readonly AvisarEventoDePedido $avisar) {}

    public function handle(PedidoAsignado $evento): void
    {
        $this->avisar->handle($evento, static fn (DestinatariosPorEvento $reglas, Padron $padron): array => $reglas->asignado($evento->participantes, $evento->actorId, $padron));
    }
}
