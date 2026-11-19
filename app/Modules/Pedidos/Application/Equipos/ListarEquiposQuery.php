<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Equipos;

use App\Modules\Pedidos\Domain\Enums\EquipoEstado;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * W-49 (RN-PED-56, resuelta DU-P18): los que no están Completada primero, por fecha (el más viejo arriba;
 * sin fecha, al final) y los Completada al final. Lo que muestra cada fila sale en pocas consultas.
 */
final class ListarEquiposQuery
{
    public const POR_PAGINA = 50;

    public const CON = ['actualizadoPor', 'inventario.subidoPor', 'presupuesto.subidoPor'];

    /**
     * @return LengthAwarePaginator<int, EquipoReparacion>
     */
    public function handle(?EquipoEstado $estado, int $pagina): LengthAwarePaginator
    {
        $query = EquipoReparacion::query()->with(self::CON);

        if ($estado !== null) {
            $query->where('estado', $estado->value);
        }

        return $query
            ->orderByRaw('estado = ?', [EquipoEstado::Completada->value])
            ->orderByRaw('fecha IS NULL')
            ->orderBy('fecha')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'page', $pagina);
    }
}
