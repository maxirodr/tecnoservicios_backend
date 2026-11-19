<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Infrastructure;

use App\Modules\Avisos\Contracts\DestinatariosDeAviso;
use App\Modules\Pedidos\Domain\Events\Participantes;

/** Hasta que Avisos registre la suya (semana 2, tanda 1). */
final class DestinatariosDeAvisoNulo implements DestinatariosDeAviso
{
    public function deAsignacion(Participantes $participantes, int $actorId): array
    {
        return [];
    }
}
