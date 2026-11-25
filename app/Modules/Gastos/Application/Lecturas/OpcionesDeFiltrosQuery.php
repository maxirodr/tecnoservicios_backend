<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Contracts\ResumenProveedor;

/**
 * Opciones de los filtros Obra y Proveedor de W-33 para quien ve (`gastos` ≥ ver, resuelta DU-G12): con
 * alcance "solo sus obras", solo sus obras y los proveedores de sus renglones; si no, todas las obras y los
 * proveedores de cualquier gasto (anulados incluidos, como la lista). Búsqueda por texto, como máximo 20
 * coincidencias: el alcance se aplica antes de cortar. Las opciones de la carga son otras (§8.2).
 */
final readonly class OpcionesDeFiltrosQuery
{
    public const MAXIMO = 20;

    public function __construct(
        private AccesoGasto $acceso,
        private ObrasConsulta $obras,
        private ProveedoresConsulta $proveedores,
    ) {}

    /**
     * @return list<array{id: int, numero: string, nombre_trabajo: string}>
     */
    public function obras(User $user, ?string $buscar): array
    {
        return array_map(static fn (ObraResumen $o): array => [
            'id' => $o->id,
            'numero' => $o->numero,
            'nombre_trabajo' => $o->nombreTrabajo,
        ], $this->obras->opciones($buscar, self::MAXIMO, $this->acceso->obrasVisibles($user)));
    }

    /**
     * @return list<array{id: int, nombre: string, cuit: string|null, es_generico: bool}>
     */
    public function proveedores(User $user, ?string $buscar): array
    {
        return array_map(static fn (ResumenProveedor $p): array => [
            'id' => $p->id->valor,
            'nombre' => $p->nombre,
            'cuit' => $p->cuit,
            'es_generico' => $p->esGenerico,
        ], $this->proveedores->opcionesEntre($this->proveedoresConGastos($user), $buscar, self::MAXIMO));
    }

    /**
     * Los proveedores de los gastos que ve (RN-GAS-48): por id, sin cruzar tablas de Proveedores.
     *
     * @return list<int>
     */
    private function proveedoresConGastos(User $user): array
    {
        $visibles = $this->acceso->obrasVisibles($user);
        $gastos = Gasto::query();

        if ($visibles !== null) {
            $gastos->whereIn('id', GastoRenglon::query()->select('gasto_id')->whereIn('obra_id', $visibles === [] ? [0] : $visibles));
        }

        return array_values(array_map('intval', $gastos->distinct()->pluck('proveedor_id')->all()));
    }
}
