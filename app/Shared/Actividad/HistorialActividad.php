<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Lectura del historial de un sujeto (F-20 W-23, F-34, F-46): el más nuevo arriba, con quién lo hizo.
 * Cada módulo arma el texto "Qué cambió" con sus palabras y filtra lo que el usuario no puede ver.
 */
final class HistorialActividad
{
    public const POR_PAGINA = 20;

    /**
     * @return LengthAwarePaginator<int, Actividad>
     */
    public function delSujeto(Model $sujeto, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        return Actividad::query()
            ->where('sujeto_type', $sujeto->getMorphClass())
            ->where('sujeto_id', $sujeto->getKey())
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($porPagina);
    }
}
