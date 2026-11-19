<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Eventos;

use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\Participantes;

/**
 * "Se le avisó a Carla" (W-43, RN-PED-08): a quiénes les llega la asignación o la reasignación. Lo decide
 * Avisos con sus reglas (`DestinatariosDeAviso`); Pedidos solo lo informa en la respuesta.
 */
final readonly class Avisados
{
    public function __construct(private DestinatariosDeAviso $destinatarios) {}

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function de(Participantes $participantes, int $actorId): array
    {
        return array_map(
            static fn (User $user): array => ['id' => $user->id, 'nombre' => $user->nombre],
            $this->destinatarios->deAsignacion($participantes, $actorId),
        );
    }
}
