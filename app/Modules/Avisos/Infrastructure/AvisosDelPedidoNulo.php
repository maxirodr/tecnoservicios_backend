<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Infrastructure;

use App\Modules\Avisos\Contracts\AvisosDelPedido;

/** Hasta que Avisos registre la suya (semana 2, tanda 1). */
final class AvisosDelPedidoNulo implements AvisosDelPedido
{
    public function enviadosDe(int $pedidoId): array
    {
        return [];
    }
}
