<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Acceso;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\AutorizaAdjuntos;
use Illuminate\Database\Eloquent\Model;

/**
 * Adjuntos de un gasto (gastos.md §4.4, identidad.md §6.6). `comprobante_gasto`: los baja quien ve el gasto
 * (con alcance; fuera de alcance, 404) y los quita quien lo corrige. `remito`: además, `control_cantidad`
 * ≥ ver lo baja y `control_cantidad = editar` lo quita, en sus obras. Un gasto anulado conserva sus
 * adjuntos (RN-GAS-40): no se quitan.
 */
final readonly class GastoAdjuntos implements AutorizaAdjuntos
{
    public function __construct(private AccesoGasto $acceso) {}

    public function puedeVer(User $user, Model $sujeto): bool
    {
        if (! $sujeto instanceof Gasto) {
            return false;
        }

        return $this->acceso->veGasto($user, $sujeto) || $this->porControlDeCantidad($user, $sujeto, Nivel::Ver);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        if (! $sujeto instanceof Gasto || $sujeto->anulado()) {
            return false;
        }

        return $this->acceso->corrigeGasto($user, $sujeto) || $this->porControlDeCantidad($user, $sujeto, Nivel::Editar);
    }

    private function porControlDeCantidad(User $user, Gasto $gasto, Nivel $nivel): bool
    {
        if ($gasto->contextoAdjuntos() !== Gasto::CONTEXTO_REMITO || ! $this->acceso->puede($user, ClaveFuncion::ControlCantidad, $nivel)) {
            return false;
        }

        $obras = $this->acceso->obrasDe($user, ClaveFuncion::ControlCantidad);

        return $obras === null || array_intersect($gasto->obraIds(), $obras) !== [];
    }
}
