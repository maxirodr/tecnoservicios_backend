<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Sectores;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * W-40 y desplegables: sectores con la cantidad de usuarios activos. Los archivados solo si se piden.
 */
final class ListarSectoresQuery
{
    private const USUARIOS_ACTIVOS = 'usuarios as usuarios_activos';

    /**
     * @return Collection<int, Sector>
     */
    public function handle(bool $incluirArchivados): Collection
    {
        return Sector::query()
            ->withCount([self::USUARIOS_ACTIVOS => self::soloActivos(...)])
            ->when(! $incluirArchivados, fn (Builder $query): Builder => $query->where('activo', true))
            ->orderBy('id')
            ->get();
    }

    /** Un solo sector (alta o renombre) con la misma cuenta que el listado. */
    public function uno(Sector $sector): Sector
    {
        return $sector->loadCount([self::USUARIOS_ACTIVOS => self::soloActivos(...)]);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private static function soloActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
