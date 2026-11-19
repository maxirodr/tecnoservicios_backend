<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application;

use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Avisos\Domain\Destinatario;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\Participantes;

/**
 * A quiénes les llega la asignación o la reasignación, con las mismas reglas que los avisos, para el
 * "Se le avisó a Carla" de W-43 (RN-AVI-28, pedidos.md RN-PED-08).
 */
final class DestinatariosDeAsignacion implements DestinatariosDeAviso
{
    public function __construct(
        private readonly PersonasDelPedido $personas,
        private readonly DestinatariosPorEvento $reglas,
    ) {}

    public function deAsignacion(Participantes $participantes, int $actorId): array
    {
        $ids = array_map(
            static fn (Destinatario $d): int => $d->userId,
            $this->reglas->asignado($participantes, $actorId, $this->personas->padron($participantes)),
        );

        if ($ids === []) {
            return [];
        }

        $usuarios = User::query()->whereIn('id', $ids)->get()->keyBy('id');

        return array_values(array_filter(array_map(static fn (int $id): ?User => $usuarios->get($id), $ids)));
    }
}
