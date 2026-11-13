<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Plazos;

use App\Modules\Identidad\Domain\Models\Plazos;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;

/**
 * F-68 / HU-011: guarda los plazos (la forma la valida el FormRequest: 0 ≤ urgente < prioritario
 * ≤ 365) y deja quién, cuándo y los valores anteriores. No recalcula pedidos cargados (RN-IDE-42).
 */
final class ActualizarPlazosAction
{
    public function __construct(private readonly RegistroActividad $actividad) {}

    public function handle(User $actor, int $urgente, int $prioritario): Plazos
    {
        return DB::transaction(function () use ($actor, $urgente, $prioritario): Plazos {
            $plazos = Plazos::query()->lockForUpdate()->firstOrFail();
            $antes = [
                'urgente_hasta_dias' => $plazos->urgente_hasta_dias,
                'prioritario_hasta_dias' => $plazos->prioritario_hasta_dias,
            ];

            $plazos->fill([
                'urgente_hasta_dias' => $urgente,
                'prioritario_hasta_dias' => $prioritario,
                'updated_by' => $actor->id,
            ]);
            $plazos->save();

            $this->actividad->registrar($plazos, 'editado', [
                'urgente_hasta_dias' => ['antes' => $antes['urgente_hasta_dias'], 'despues' => $urgente],
                'prioritario_hasta_dias' => ['antes' => $antes['prioritario_hasta_dias'], 'despues' => $prioritario],
            ]);

            return $plazos;
        });
    }
}
