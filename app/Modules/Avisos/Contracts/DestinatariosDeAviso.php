<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Contracts;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\Participantes;

/** Avisos lo implementa; Pedidos lo usa para el "Se le avisó a Carla" de W-43 (pedidos.md RN-PED-08). */
interface DestinatariosDeAviso
{
    /**
     * A quiénes les llega la asignación o la reasignación, con las reglas de avisos.md §5.
     *
     * @return list<User>
     */
    public function deAsignacion(Participantes $participantes, int $actorId): array;
}
