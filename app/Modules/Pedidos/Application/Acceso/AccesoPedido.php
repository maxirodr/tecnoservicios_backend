<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Acceso;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Visibilidad;

/**
 * Quién ve y quién hace qué con un pedido (pedidos.md §7), por la matriz (ADR-006) más las reglas fijas
 * de §7.2. "Lo propio" (lo pidió, lo tiene, lo sigue o está en su sector sin propietario) se ve y se
 * resuelve aunque la tabla le saque el acceso después (resuelta DU-P01).
 */
final readonly class AccesoPedido
{
    public function __construct(
        private Permisos $permisos,
        private MiGente $gente,
        private Seguimiento $seguimiento,
    ) {}

    public function puedeNivel(User $user, Nivel $nivel): bool
    {
        return $this->permisos->puede($user, ClaveFuncion::Pedidos->value, $nivel);
    }

    public function alcanceTodas(User $user): bool
    {
        return $this->permisos->alcance($user, ClaveFuncion::Pedidos->value) === Alcance::Todas;
    }

    /** El alcance de los listados de quien mira (RN-PED-33), para `VisiblesPara`. */
    public function visibilidad(User $user): Visibilidad
    {
        $conVer = $this->puedeNivel($user, Nivel::Ver);

        if ($conVer && $this->alcanceTodas($user)) {
            return Visibilidad::todos();
        }

        return new Visibilidad(false, $user->id, $user->sector_id, $conVer, $conVer ? $this->gente->ids($user) : []);
    }

    public function esPropio(User $user, Pedido $pedido): bool
    {
        return $pedido->esSolicitante($user)
            || $pedido->esPropietario($user)
            || $pedido->esDelSectorSinPropietario($user)
            || $this->seguimiento->sigue($user, $pedido);
    }

    /** RN-PED-32…34: lo propio siempre; con Ver, todos (alcance todas) o también los de mi gente (propias). */
    public function ver(User $user, Pedido $pedido): bool
    {
        if ($this->esPropio($user, $pedido)) {
            return true;
        }

        if (! $this->puedeNivel($user, Nivel::Ver)) {
            return false;
        }

        return $this->alcanceTodas($user) || ($pedido->propietario_id !== null && $this->gente->incluye($user, $pedido->propietario_id));
    }

    public function editar(User $user, Pedido $pedido): bool
    {
        return $this->puedeNivel($user, Nivel::Editar) && $this->ver($user, $pedido);
    }

    /** Tomar, bloquear, retomar y completar: el propietario o, en el sector, una persona activa del sector. */
    public function actuaComoPropietario(User $user, Pedido $pedido): bool
    {
        return $pedido->esPropietario($user) || $pedido->esDelSectorSinPropietario($user);
    }

    /** Quien lo pidió, con Pedidos = Editar; sin solicitante (carga inicial), cualquiera con Editar (DU-P10). */
    public function reabrir(User $user, Pedido $pedido): bool
    {
        return $this->editar($user, $pedido) && ($pedido->solicitante_id === null || $pedido->esSolicitante($user));
    }

    /** RN-PED-22: el propietario (o del sector) o quien lo pidió, con Pedidos = Editar (DU-P01, DU-P02). */
    public function reasignar(User $user, Pedido $pedido): bool
    {
        return $this->editar($user, $pedido) && ($this->actuaComoPropietario($user, $pedido) || $pedido->esSolicitante($user));
    }

    /** RN-PED-12: la prioridad la corrige solo quien lo pidió. */
    public function corregirPrioridad(User $user, Pedido $pedido): bool
    {
        return $pedido->esSolicitante($user) && $this->editar($user, $pedido);
    }

    /** RN-PED-26: un pedido de otro (no lo pidió ni lo tiene), que puede ver, con Pedidos ≥ Ver. */
    public function seguir(User $user, Pedido $pedido, bool $sigo): bool
    {
        return ! $sigo && ! $pedido->esSolicitante($user) && ! $pedido->esPropietario($user)
            && $this->puedeNivel($user, Nivel::Ver) && $this->ver($user, $pedido);
    }

    /**
     * Botones del detalle (§7.3); la API vuelve a verificar cada acción.
     *
     * @return array<string, bool>
     */
    public function resumen(User $user, Pedido $pedido, bool $sigo): array
    {
        $actua = $this->actuaComoPropietario($user, $pedido);
        $estado = $pedido->estado;
        $tomable = $estado === PedidoEstado::NoIniciada;
        $completable = $tomable || $estado === PedidoEstado::EnCurso;

        return [
            'tomar' => $actua && $tomable,
            'bloquear' => $actua && $completable,
            'retomar' => $actua && $estado === PedidoEstado::Bloqueada,
            'completar' => $actua && $completable,
            'reabrir' => $estado === PedidoEstado::Completada && $this->reabrir($user, $pedido),
            'reasignar' => $estado->abierto() && $this->reasignar($user, $pedido),
            'editar' => $this->editar($user, $pedido),
            'corregir_prioridad' => $this->corregirPrioridad($user, $pedido),
            'comentar' => $this->ver($user, $pedido),
            'seguir' => $this->seguir($user, $pedido, $sigo),
            'dejar_de_seguir' => $sigo,
        ];
    }
}
