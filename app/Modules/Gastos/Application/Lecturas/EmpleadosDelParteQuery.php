<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Query\Builder;

/**
 * RN-GAS-56: los empleados ya cargados en partes de horas, para sugerirlos en el campo Empleado (máx. 20, el usado
 * más reciente primero, sin repetir: la comparación no distingue mayúsculas ni tildes). Con "solo sus obras", solo
 * los de renglones de sus obras.
 */
final readonly class EmpleadosDelParteQuery
{
    public const LIMITE = 20;

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
            ->groupBy('gasto_renglones.empleado')
            ->orderByRaw('MAX(gasto_renglones.id) DESC')
            ->limit(self::LIMITE)
            ->pluck('gasto_renglones.empleado');

        return array_values($nombres->map(static fn (mixed $nombre): string => is_scalar($nombre) ? (string) $nombre : '')->all());
    }
}
