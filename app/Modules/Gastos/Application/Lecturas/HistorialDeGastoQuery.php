<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Correccion\EscribirCorreccion;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\Actividad;

/**
 * F-14 · F-46 · W-34: el historial del gasto, del más viejo al más nuevo (RN-GAS-42). Con "solo sus obras"
 * no aparecen los cambios de renglones de otras obras o sin obra (RN-GAS-48).
 */
final readonly class HistorialDeGastoQuery
{
    public function __construct(
        private BuscarGasto $buscar,
        private AccesoGasto $acceso,
    ) {}

    /**
     * @return list<array{cuando: string|null, quien: array{id: int, nombre: string}|null, que: string}>
     */
    public function handle(User $user, int $id): array
    {
        $gasto = $this->buscar->visible($user, $id);
        $visibles = $this->acceso->obrasVisibles($user);

        $actividades = Actividad::query()
            ->where('sujeto_type', $gasto->getMorphClass())
            ->where('sujeto_id', $gasto->id)
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $lineas = [];

        foreach ($actividades as $actividad) {
            if (! self::laVe($actividad, $visibles)) {
                continue;
            }

            foreach (TextoDeHistorial::lineas($actividad, $gasto->origen) as $que) {
                $lineas[] = [
                    'cuando' => FechaEmpresa::instante($actividad->created_at),
                    'quien' => $actividad->user === null ? null : ['id' => $actividad->user->id, 'nombre' => $actividad->user->nombre],
                    'que' => $que,
                ];
            }
        }

        return $lineas;
    }

    /**
     * @param  list<int>|null  $visibles
     */
    private static function laVe(Actividad $actividad, ?array $visibles): bool
    {
        $obras = $actividad->cambios[EscribirCorreccion::OBRAS] ?? null;

        if ($visibles === null || $obras === null) {
            return true;
        }

        $ids = array_merge(is_array($obras['antes']) ? $obras['antes'] : [], is_array($obras['despues']) ? $obras['despues'] : []);

        return array_intersect(array_filter($ids, 'is_int'), $visibles) !== [];
    }
}
