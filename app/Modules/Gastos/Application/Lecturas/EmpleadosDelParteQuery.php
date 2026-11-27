<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * RN-GAS-56: los empleados ya cargados en partes de horas, para sugerirlos en el campo Empleado (máx. 20, el usado
 * más reciente primero, sin repetir: la comparación no distingue mayúsculas ni tildes). Con "solo sus obras", solo
 * los de renglones de sus obras.
 */
final readonly class EmpleadosDelParteQuery
{
    public const LIMITE = 20;

    /** Renglones que se miran para armar la lista (los más nuevos). */
    private const LEIDOS = 2000;

    public function __construct(private AccesoGasto $acceso) {}

    /**
     * @return list<string>
     */
    public function handle(User $user, ?string $buscar): array
    {
        $obras = $this->acceso->obrasParaCargar($user, Origen::ParteHoras);

        $nombres = GastoRenglon::query()->toBase()
            ->join('gastos', 'gastos.id', '=', 'gasto_renglones.gasto_id')
            ->where('gastos.origen', Origen::ParteHoras->value)
            ->whereNotNull('gasto_renglones.empleado')
            ->when($obras !== null, static fn (Builder $q): Builder => $q->whereIn('gasto_renglones.obra_id', $obras === [] ? [0] : $obras))
            ->when($buscar !== null, static fn (Builder $q): Builder => $q->where('gasto_renglones.empleado', 'like', '%'.addcslashes((string) $buscar, '\\%_').'%'))
            ->orderByDesc('gasto_renglones.id')
            ->limit(self::LEIDOS)
            ->pluck('gasto_renglones.empleado');

        // Sin repetir, con la forma en que se escribió la última vez ("Juan Perez" y "Juan Pérez" son uno).
        $unicos = [];

        foreach ($nombres as $nombre) {
            $texto = is_scalar($nombre) ? (string) $nombre : '';
            $unicos[Str::of($texto)->ascii()->lower()->squish()->toString()] ??= $texto;
        }

        return array_slice(array_values($unicos), 0, self::LIMITE);
    }
}
