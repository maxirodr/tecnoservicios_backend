<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Infrastructure\Consultas\FiltroObras;
use App\Modules\Obras\Infrastructure\Consultas\ObrasQuery;
use App\Modules\Obras\Infrastructure\Consultas\PorBusqueda;
use App\Modules\Obras\Infrastructure\Consultas\PorResponsable;
use App\Shared\Busqueda\FuenteBusqueda;
use App\Shared\Busqueda\ResultadoBusqueda;

/**
 * Grupo "Obras" del buscador general (F-03, HU-022 c. 1 y 2): la misma búsqueda del listado
 * (RN-OBR-05: N.º de cualquier presupuesto, cliente, planta o trabajo, sin tildes), en todos los
 * estados (las terminadas también, con su estado) y con el alcance "solo sus obras".
 */
final readonly class ObrasEnBuscador implements FuenteBusqueda
{
    public const TIPO = 'obras';

    /** Activas primero: En ejecución, Confirmada, Terminada (W-05). */
    private const ORDEN_ESTADOS = "FIELD(obras.estado, 'en_ejecucion', 'confirmada', 'terminada')";

    public function __construct(
        private ObrasQuery $obras,
        private AccesoObra $acceso,
    ) {}

    public function puedeBuscar(User $user): bool
    {
        return $this->acceso->puedeNivel($user, ClaveFuncion::Obras, Nivel::Ver);
    }

    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda
    {
        $filtros = $this->filtros($texto, $user);
        $cantidad = $this->obras->base($filtros)->count();
        $primeras = $this->obras->base($filtros)
            ->with('principal')
            ->orderByRaw(self::ORDEN_ESTADOS)
            ->orderByDesc('obras.id')
            ->limit($limite)
            ->get();

        return new ResultadoBusqueda($cantidad, array_values($primeras->map(static fn (Obra $obra): array => [
            'id' => $obra->id,
            'numero' => $obra->principal?->numero,
            'revision' => $obra->principal?->revision,
            'nombre_trabajo' => $obra->nombre_trabajo,
            'cliente' => $obra->cliente,
            'planta' => $obra->planta,
            'estado' => $obra->estado->value,
        ])->all()));
    }

    /**
     * @return list<FiltroObras>
     */
    private function filtros(string $texto, User $user): array
    {
        $filtros = [new PorBusqueda($texto)];

        if ($this->acceso->soloPropias($user, ClaveFuncion::Obras)) {
            $filtros[] = new PorResponsable($user->id);
        }

        return $filtros;
    }
}
