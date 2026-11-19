<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Consultas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Las pestañas de Inicio (W-01, RN-PED-37…40): Mis tareas, Pedidos que pedí y Tareas que sigo. No son
 * filas de la matriz: cada uno ve solo lo suyo, aunque la tabla le saque Pedidos (resuelta DU-P01).
 */
final readonly class ListasDeInicio
{
    public const POR_PAGINA = 50;

    /** Lo que necesita cada fila (`PedidoFila`), sin N+1. */
    public const CON = ['obra.principal', 'sector', 'propietario', 'solicitante'];

    /**
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function misTareas(User $user, int|string|null $obra, string $estado, ?string $buscar, int $pagina): LengthAwarePaginator
    {
        $query = PedidosQuery::misTareas(PedidosQuery::nueva(self::CON), $user);
        PedidosQuery::porTarea(PedidosQuery::porObra(PedidosQuery::porEstado($query, $estado), $obra), $buscar);

        return PedidosQuery::porEntrega($query)->paginate(self::POR_PAGINA, ['pedidos.*'], 'page', $pagina);
    }

    /**
     * "6 abiertas · 2 vencidas" de Mis tareas, sin los filtros de la pantalla.
     *
     * @return array{abiertas: int, vencidas: int}
     */
    public function contadoresDeMisTareas(User $user): array
    {
        return [
            'abiertas' => PedidosQuery::porEstado(PedidosQuery::misTareas(Pedido::query(), $user), PedidosQuery::ABIERTOS)->count(),
            'vencidas' => PedidosQuery::vencidosAl(PedidosQuery::misTareas(Pedido::query(), $user), FechaEmpresa::hoyTexto())->count(),
        ];
    }

    /**
     * Abiertos por entrega; con "Todos", los completados al final, el más reciente primero (RN-PED-39).
     *
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function quePedi(User $user, string $estado, int $pagina): LengthAwarePaginator
    {
        $query = PedidosQuery::porEstado(PedidosQuery::quePedi(PedidosQuery::nueva(self::CON), $user), $estado)
            ->withCount('comprobantes')
            ->orderByRaw("pedidos.estado = 'completada'")
            ->orderByDesc('pedidos.completado_at');

        return PedidosQuery::porEntrega($query)->paginate(self::POR_PAGINA, ['pedidos.*'], 'page', $pagina);
    }

    /**
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function queSigo(User $user, int $pagina): LengthAwarePaginator
    {
        $query = PedidosQuery::porEstado(PedidosQuery::queSigo(PedidosQuery::nueva(self::CON), $user), PedidosQuery::ABIERTOS);

        return PedidosQuery::porEntrega($query)->paginate(self::POR_PAGINA, ['pedidos.*'], 'page', $pagina);
    }
}
