<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A quién se le puede dar un pedido (al crear, reasignar o reabrir): una persona **activa** de un sector
 * con Pedidos ≥ Ver (RN-PED-03, resuelta DU-P01), o un sector activo con al menos una persona activa
 * que pueda verlo (RN-PED-23, resuelta DU-P02). Si no, 422 en el campo.
 */
final readonly class Asignables
{
    public function __construct(private Permisos $permisos) {}

    /**
     * @throws ValidationException
     */
    public function persona(int $id, string $campo = 'propietario_id'): User
    {
        $persona = User::query()->with('sector')->whereKey($id)->where('activo', true)->first();

        if (! $persona instanceof User) {
            throw ValidationException::withMessages([$campo => ['Elegí una persona activa.']]);
        }

        if (! $this->permisos->puede($persona, ClaveFuncion::Pedidos->value, Nivel::Ver)) {
            throw ValidationException::withMessages([$campo => ["{$persona->nombre} no tiene acceso a Tareas: elegí otra persona."]]);
        }

        return $persona;
    }

    /**
     * @throws ValidationException
     */
    public function sector(int $id, string $campo = 'sector_id'): Sector
    {
        $sector = Sector::query()->whereKey($id)->where('activo', true)->first();

        if (! $sector instanceof Sector) {
            throw ValidationException::withMessages([$campo => ['Elegí un sector activo.']]);
        }

        return $sector;
    }

    /**
     * @throws ValidationException
     */
    public function sectorConGente(int $id, string $campo = 'sector_id'): Sector
    {
        $sector = $this->sector($id, $campo);
        $activos = User::query()->where('sector_id', $sector->id)->where('activo', true)->get();

        if ($activos->isEmpty()) {
            throw ValidationException::withMessages([$campo => ["{$sector->nombre} no tiene usuarios activos: elegí una persona."]]);
        }

        if (! $this->permisos->puede($activos->firstOrFail(), ClaveFuncion::Pedidos->value, Nivel::Ver)) {
            throw ValidationException::withMessages([$campo => ["{$sector->nombre} no tiene acceso a Tareas: elegí una persona."]]);
        }

        return $sector;
    }
}
