<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * RN-IDE-21: listado de W-15 (Nombre · Mail · Sector · Activo). Por defecto solo activos; busca
 * por nombre o mail; filtra por sector.
 */
final class ListarUsuariosQuery
{
    public const ACTIVOS = 'true';

    public const INACTIVOS = 'false';

    public const TODOS = 'todos';

    private const POR_PAGINA = 25;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(?string $buscar, ?int $sectorId, string $activo): LengthAwarePaginator
    {
        return User::query()
            ->with(['sector', 'jefe'])
            ->when($buscar !== null && $buscar !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $texto): Builder => $texto
                    ->where('nombre', 'like', TextoLike::contiene((string) $buscar))
                    ->orWhere('email', 'like', TextoLike::contiene((string) $buscar)),
            ))
            ->when($sectorId !== null, fn (Builder $query): Builder => $query->where('sector_id', $sectorId))
            ->when($activo !== self::TODOS, fn (Builder $query): Builder => $query->where('activo', $activo === self::ACTIVOS))
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA);
    }
}
