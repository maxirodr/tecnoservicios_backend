<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Acceso;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;

/**
 * Quién ve y quién hace qué con un gasto (gastos.md §8), siempre por la matriz (ADR-006). Con alcance
 * "solo sus obras" se ven solo los renglones de las obras donde es responsable (RN-GAS-48) y se corrige
 * solo lo que es todo suyo (RN-GAS-49). Las cargas desde la rendición piden `rendicion = editar`; los
 * retiros, también `control_cantidad = editar` (§8.1, resuelta DU-R05). Se recuerda por pedido al servidor.
 */
final class AccesoGasto
{
    /** @var array<int, list<int>> */
    private array $responsable = [];

    public function __construct(
        private readonly Permisos $permisos,
        private readonly ObrasConsulta $obras,
    ) {}

    public function puede(User $user, ClaveFuncion $funcion, Nivel $nivel): bool
    {
        return $this->permisos->puede($user, $funcion->value, $nivel);
    }

    /**
     * Obras que alcanza la celda: null = todas; si no, las obras donde es responsable.
     *
     * @return list<int>|null
     */
    public function obrasDe(User $user, ClaveFuncion $funcion): ?array
    {
        if ($this->permisos->alcance($user, $funcion->value) === Alcance::Todas) {
            return null;
        }

        return $this->responsable[$user->id] ??= $this->obras->obrasDondeEsResponsable($user->id);
    }

    /**
     * Obras cuyos renglones ve en Gastos (RN-GAS-48): null = todas.
     *
     * @return list<int>|null
     */
    public function obrasVisibles(User $user): ?array
    {
        return $this->obrasDe($user, ClaveFuncion::Gastos);
    }

    public function veGasto(User $user, Gasto $gasto): bool
    {
        if (! $this->puede($user, ClaveFuncion::Gastos, Nivel::Ver)) {
            return false;
        }

        $visibles = $this->obrasVisibles($user);

        return $visibles === null || array_intersect($gasto->obraIds(), $visibles) !== [];
    }

    /** RN-GAS-49: con "solo sus obras", solo si todos sus renglones son de sus obras. */
    public function corrigeGasto(User $user, Gasto $gasto): bool
    {
        if (! $this->puede($user, ClaveFuncion::Gastos, Nivel::Editar)) {
            return false;
        }

        $visibles = $this->obrasVisibles($user);

        return $visibles === null || $this->todosEn($gasto, $visibles);
    }

    /** La celda de la carga, sin mirar la obra (la obra la mira `obrasParaCargar`). */
    public function cargaOrigen(User $user, Origen $origen): bool
    {
        return $this->funcionesDeCarga($user, $origen) !== [];
    }

    /** Alguna de las tres celdas que cargan (gastos.md §8.2: opciones y aviso de factura repetida). */
    public function cargaAlgo(User $user): bool
    {
        foreach ([ClaveFuncion::Gastos, ClaveFuncion::Rendicion, ClaveFuncion::ControlCantidad] as $funcion) {
            if ($this->puede($user, $funcion, Nivel::Editar)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A qué obras puede imputar esta carga: null = todas (y "sin obra"); si no, solo esas.
     *
     * @return list<int>|null
     */
    public function obrasParaCargar(User $user, Origen $origen): ?array
    {
        $obras = [];

        foreach ($this->funcionesDeCarga($user, $origen) as $funcion) {
            $deLaCelda = $this->obrasDe($user, $funcion);

            if ($deLaCelda === null) {
                return null;
            }

            $obras = [...$obras, ...$deLaCelda];
        }

        return array_values(array_unique($obras));
    }

    /** Nombre y valor hora de cada renglón del parte de horas (RN-GAS-57): `gastos = editar`. */
    public function veDetallePorPersona(User $user): bool
    {
        return $this->puede($user, ClaveFuncion::Gastos, Nivel::Editar);
    }

    /** Estado de pago de las facturas y el filtro Pago (RN-GAS-61…63): `pagos` ≥ ver. */
    public function vePagos(User $user): bool
    {
        return $this->puede($user, ClaveFuncion::Pagos, Nivel::Ver);
    }

    /** Marcar como paga o impaga (RN-GAS-59): `pagos = editar`. */
    public function marcaPagos(User $user): bool
    {
        return $this->puede($user, ClaveFuncion::Pagos, Nivel::Editar);
    }

    /**
     * @return list<ClaveFuncion> las celdas en Editar que habilitan esta carga
     */
    private function funcionesDeCarga(User $user, Origen $origen): array
    {
        $candidatas = match (true) {
            $origen === Origen::RetiroRemito => [ClaveFuncion::Rendicion, ClaveFuncion::ControlCantidad],
            $origen->seCargaDesdeLaRendicion() => [ClaveFuncion::Rendicion],
            default => [ClaveFuncion::Gastos],
        };

        return array_values(array_filter($candidatas, fn (ClaveFuncion $f): bool => $this->puede($user, $f, Nivel::Editar)));
    }

    /**
     * @param  list<int>  $obras
     */
    private function todosEn(Gasto $gasto, array $obras): bool
    {
        foreach ($gasto->loadMissing('renglones')->renglones as $renglon) {
            if ($renglon->obra_id === null || ! in_array($renglon->obra_id, $obras, true)) {
                return false;
            }
        }

        return true;
    }
}
