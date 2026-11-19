<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Bandeja;

use App\Modules\Avisos\Domain\Models\Aviso;
use App\Modules\Avisos\Domain\TipoAviso;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Campana (los 5 más recientes) y "Todos los avisos" (de a 25): solo los míos, la más nueva arriba (RN-AVI-23, RN-AVI-24). */
final class ListarAvisosQuery
{
    /**
     * @param  bool|null  $leidos  null = todos
     * @return LengthAwarePaginator<int, Aviso>
     */
    public function handle(int $userId, ?bool $leidos, ?TipoAviso $tipo, int $porPagina, int $pagina): LengthAwarePaginator
    {
        return Aviso::query()
            ->where('user_id', $userId)
            ->when($leidos === true, static fn ($q) => $q->whereNotNull('leido_at'))
            ->when($leidos === false, static fn ($q) => $q->whereNull('leido_at'))
            ->when($tipo !== null, static fn ($q) => $q->where('tipo', $tipo?->value))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($porPagina, ['*'], 'page', $pagina);
    }
}
