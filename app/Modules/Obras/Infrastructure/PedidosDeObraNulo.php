<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Obras\Application\Ports\PedidosDeObra;

/** Semana 1: no hay módulo Pedidos (DU-A12). Pedidos registra el suyo en la semana 2. */
final class PedidosDeObraNulo implements PedidosDeObra
{
    public function abiertosDe(int $obraId): array
    {
        return [];
    }

    public function contarAbiertosPorObra(array $obraIds): array
    {
        return array_fill_keys($obraIds, null);
    }

    public function contarAbiertosSinObra(): ?int
    {
        return null;
    }
}
