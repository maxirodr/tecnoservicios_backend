<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Punto de partida y filtros de las listas de Inicio (RN-PED-38…40): lo mío, lo que pedí, lo que sigo,
 * por estado, por obra, por texto en la tarea y el orden por entrega (vencidos arriba) y prioridad.
 */
final class PedidosQuery
{
    public const ABIERTOS = 'abiertos';

    public const TODOS = 'todos';

    public const SIN_OBRA = 'sin_obra';

    /**
     * @param  list<string>  $con
     * @return Builder<Pedido>
     */
    public static function nueva(array $con = []): Builder
    {
        return Pedido::query()->with($con);
    }

    /**
     * Mis tareas: soy propietario o está en mi sector sin propietario (RN-PED-38, RN-PED-24).
     *
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function misTareas(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('pedidos.propietario_id', $user->id)
            ->orWhere(fn (Builder $s) => $s->whereNull('pedidos.propietario_id')->where('pedidos.sector_id', $user->sector_id)));
    }

    /**
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function quePedi(Builder $query, User $user): Builder
    {
        return $query->where('pedidos.solicitante_id', $user->id);
    }

    /**
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function queSigo(Builder $query, User $user): Builder
    {
        return $query->whereExists(fn (QueryBuilder $s) => $s->selectRaw('1')->from('pedido_seguidores')
            ->whereColumn('pedido_seguidores.pedido_id', 'pedidos.id')
            ->where('pedido_seguidores.user_id', $user->id));
    }

    /**
     * `abiertos` (por defecto), `todos` o un estado.
     *
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function porEstado(Builder $query, string $estado): Builder
    {
        return match ($estado) {
            self::TODOS => $query,
            self::ABIERTOS => $query->whereIn('pedidos.estado', PedidoEstado::abiertos()),
            default => $query->where('pedidos.estado', PedidoEstado::from($estado)->value),
        };
    }

    /**
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function porObra(Builder $query, int|string|null $obra): Builder
    {
        return match (true) {
            $obra === null => $query,
            $obra === self::SIN_OBRA => $query->whereNull('pedidos.obra_id'),
            default => $query->where('pedidos.obra_id', (int) $obra),
        };
    }

    /**
     * Texto en la tarea, con `LIKE` parametrizado y comodines escapados (TL-09).
     *
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function porTarea(Builder $query, ?string $texto): Builder
    {
        return $texto === null ? $query : $query->where('pedidos.tarea', 'like', '%'.addcslashes($texto, '\\%_').'%');
    }

    /**
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function vencidosAl(Builder $query, string $hoy): Builder
    {
        return $query->whereIn('pedidos.estado', PedidoEstado::abiertos())->where('pedidos.fecha_entrega', '<', $hoy);
    }

    /**
     * Entrega más próxima primero (los vencidos quedan arriba; sin entrega, al final), después prioridad
     * (Urgente, Prioritario, Normal) y, a igual valor, el más nuevo primero.
     *
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function porEntrega(Builder $query): Builder
    {
        return $query->orderByRaw('pedidos.fecha_entrega IS NULL')
            ->orderBy('pedidos.fecha_entrega')
            ->orderByRaw('FIELD(pedidos.prioridad, ?, ?, ?)', [Prioridad::Urgente->value, Prioridad::Prioritario->value, Prioridad::Normal->value])
            ->orderByDesc('pedidos.id');
    }
}
