<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Ports;

/**
 * Puerto que implementa Pedidos (semana 2, obras.md §10.4). Obras lo define para no depender de
 * Pedidos. `null` = "todavía no hay módulo Pedidos" (se muestra "—", RN-OBR-03).
 */
interface PedidosDeObra
{
    /**
     * @return list<PedidoAbiertoResumen>
     */
    public function abiertosDe(int $obraId): array;

    /**
     * Una consulta para toda la página del listado.
     *
     * @param  list<int>  $obraIds
     * @return array<int, int|null> obraId => cantidad de pedidos abiertos
     */
    public function contarAbiertosPorObra(array $obraIds): array;

    public function contarAbiertosSinObra(): ?int;
}
