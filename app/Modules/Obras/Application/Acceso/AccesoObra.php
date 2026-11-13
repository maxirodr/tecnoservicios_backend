<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Acceso;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;

/**
 * Qué puede hacer una persona con una obra (obras.md §7), siempre por la matriz (ADR-006).
 * Alcance "propias" = obras donde es responsable. Cambiar el estado: el responsable (regla fija)
 * o `estado_obra = editar` (RN-OBR-30).
 */
final readonly class AccesoObra
{
    public function __construct(private Permisos $permisos) {}

    public function puede(User $user, ClaveFuncion $funcion, Nivel $nivel, Obra $obra): bool
    {
        return $this->permisos->puede($user, $funcion->value, $nivel)
            && ($this->permisos->alcance($user, $funcion->value) === Alcance::Todas || $obra->esResponsable($user));
    }

    /** Con alcance "propias" en esa función, los listados se limitan a sus obras. */
    public function soloPropias(User $user, ClaveFuncion $funcion): bool
    {
        return $this->permisos->alcance($user, $funcion->value) === Alcance::Propias;
    }

    public function puedeNivel(User $user, ClaveFuncion $funcion, Nivel $nivel): bool
    {
        return $this->permisos->puede($user, $funcion->value, $nivel);
    }

    public function verObra(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::Obras, Nivel::Ver, $obra);
    }

    public function editarObra(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::Obras, Nivel::Editar, $obra);
    }

    public function cambiarEstado(User $user, Obra $obra): bool
    {
        return $obra->esResponsable($user) || $this->permisos->puede($user, ClaveFuncion::EstadoObra->value, Nivel::Editar);
    }

    public function verMontos(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::PresupuestoRubro, Nivel::Ver, $obra);
    }

    public function cargarMontos(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::PresupuestoRubro, Nivel::Editar, $obra);
    }

    public function verMontoObra(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::MontoObra, Nivel::Ver, $obra);
    }

    public function cargarMontoObra(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::MontoObra, Nivel::Editar, $obra);
    }

    public function verRendicion(User $user, Obra $obra): bool
    {
        return $this->puede($user, ClaveFuncion::Rendicion, Nivel::Ver, $obra);
    }

    public function verPedidos(User $user): bool
    {
        return $this->permisos->puede($user, ClaveFuncion::Pedidos->value, Nivel::Ver);
    }

    /**
     * Lo que la ficha necesita para mostrar u ocultar botones (obras.md §7); la API vuelve a verificar.
     *
     * @return array<string, bool>
     */
    public function resumen(User $user, Obra $obra): array
    {
        return [
            'editar' => $this->editarObra($user, $obra),
            'cambiar_estado' => $this->cambiarEstado($user, $obra),
            'ver_montos' => $this->verMontos($user, $obra),
            'cargar_montos' => $this->cargarMontos($user, $obra),
            'ver_monto_obra' => $this->verMontoObra($user, $obra),
            'cargar_monto_obra' => $this->cargarMontoObra($user, $obra),
            'ver_rendicion' => $this->verRendicion($user, $obra),
            'ver_pedidos' => $this->verPedidos($user),
        ];
    }
}
