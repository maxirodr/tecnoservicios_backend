<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\EquipoReparacion;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Inventario y presupuesto del taller (contextos `equipo_inventario` y `equipo_presupuesto`, pedidos.md
 * §4.5): los baja quien tiene Equipos en reparación ≥ Ver (sin acceso: 404) y los quita quien tiene = Editar.
 */
final readonly class EquipoAdjuntos implements AutorizaAdjuntos
{
    public function __construct(private Permisos $permisos) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        return $sujeto instanceof EquipoReparacion && $this->permisos->puede($user, ClaveFuncion::EquiposReparacion->value, Nivel::Ver);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        return $sujeto instanceof EquipoReparacion && $this->permisos->puede($user, ClaveFuncion::EquiposReparacion->value, Nivel::Editar);
    }
}
