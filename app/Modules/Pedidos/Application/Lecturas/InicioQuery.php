<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\MiGente;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;

/**
 * `GET /api/pedidos/inicio` (RN-PED-37): los números de las pestañas (abiertos) y si ve "Tareas de mi
 * gente": al menos un usuario activo a cargo, o Dirección (RN-PED-35).
 */
final readonly class InicioQuery
{
    public function __construct(
        private ListasDeInicio $listas,
        private MiGente $gente,
    ) {}

    /**
     * @return array{mis_tareas: int, vencidas: int, pedidos_que_pedi: int, tareas_que_sigo: int, tiene_gente: bool}
     */
    public function handle(User $user): array
    {
        $mias = $this->listas->contadoresDeMisTareas($user);

        return [
            'mis_tareas' => $mias['abiertas'],
            'vencidas' => $mias['vencidas'],
            'pedidos_que_pedi' => PedidosQuery::porEstado(PedidosQuery::quePedi(Pedido::query(), $user), PedidosQuery::ABIERTOS)->count(),
            'tareas_que_sigo' => PedidosQuery::porEstado(PedidosQuery::queSigo(Pedido::query(), $user), PedidosQuery::ABIERTOS)->count(),
            'tiene_gente' => $this->gente->tieneGenteActiva($user),
        ];
    }
}
