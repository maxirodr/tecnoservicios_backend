<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure\Consultas;

use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Visibilidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/** El alcance de quien mira (RN-PED-33), que se aplica antes que cualquier otro filtro. */
final class VisiblesPara
{
    /**
     * @param  Builder<Pedido>  $query
     * @return Builder<Pedido>
     */
    public static function aplicar(Builder $query, Visibilidad $visibilidad): Builder
    {
        if ($visibilidad->todos) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($visibilidad): void {
            $q->where('pedidos.solicitante_id', $visibilidad->userId)
                ->orWhere('pedidos.propietario_id', $visibilidad->userId)
                ->orWhere(fn (Builder $s) => $s->whereNull('pedidos.propietario_id')->where('pedidos.sector_id', $visibilidad->sectorId))
                ->orWhereExists(fn (QueryBuilder $s) => $s->selectRaw('1')->from('pedido_seguidores')
                    ->whereColumn('pedido_seguidores.pedido_id', 'pedidos.id')
                    ->where('pedido_seguidores.user_id', $visibilidad->userId));

            if ($visibilidad->conGente && $visibilidad->gente === null) {
                $q->orWhereNotNull('pedidos.propietario_id');
            } elseif ($visibilidad->conGente && $visibilidad->gente !== []) {
                $q->orWhereIn('pedidos.propietario_id', $visibilidad->gente);
            }
        });
    }
}
