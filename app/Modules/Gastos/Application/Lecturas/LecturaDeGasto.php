<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use App\Modules\Gastos\Infrastructure\Consultas\CriterioDeRenglones;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Arma `GastoLeido` (W-33, W-34, W-46, buscador): los renglones que ve, cuántos no ve, el total de lo que
 * ve y, con filtro de obra o rubro, la parte que cumple (resuelta DU-G06). Sin consultas por gasto:
 * todo sale de las relaciones ya cargadas (`FILA` o `DETALLE`).
 */
final readonly class LecturaDeGasto
{
    /** @var list<string> */
    public const FILA = ['renglones.obra', 'proveedor', 'cargadoPor'];

    /** @var list<string> */
    public const DETALLE = ['renglones.obra.principal', 'renglones.material', 'proveedor', 'cargadoPor', 'editadoPor', 'anuladoPor', 'adjuntos.subidoPor'];

    public function __construct(private AccesoGasto $acceso) {}

    /** `$completo`: W-46, donde los renglones son todos de la obra que se mira (§8.2, `rendicion` o `control_cantidad`). */
    public function de(User $user, Gasto $gasto, ?CriterioDeRenglones $filtro = null, bool $completo = false): GastoLeido
    {
        $visibles = $completo ? null : $this->acceso->obrasVisibles($user);
        $todos = array_values($gasto->renglones->all());
        $veo = $visibles === null ? $todos : array_values(array_filter(
            $todos,
            static fn (GastoRenglon $r): bool => $r->obra_id !== null && in_array($r->obra_id, $visibles, true),
        ));
        $filtrados = $filtro === null || ! $filtro->filtraRenglones()
            ? null
            : array_values(array_filter($veo, static fn (GastoRenglon $r): bool => $filtro->cumple($r)));
        $editar = ! $gasto->anulado() && $this->acceso->corrigeGasto($user, $gasto);

        return new GastoLeido(
            $gasto,
            $veo,
            count($todos) - count($veo),
            self::suma($veo),
            $filtrados === null ? ($visibles === null ? null : self::suma($veo)) : self::suma($filtrados),
            EtiquetaDocumento::de($gasto->origen, $gasto->numero, $gasto->periodo_desde, $gasto->periodo_hasta),
            // RN-IMP-27: la carga inicial solo se anula.
            ['editar' => $editar && $gasto->origen !== Origen::SaldoInicial, 'anular' => $editar],
        );
    }

    /**
     * @param  list<GastoRenglon>  $renglones
     * @return numeric-string
     */
    public static function suma(array $renglones): string
    {
        return TotalDeRenglon::suma(array_map(static fn (GastoRenglon $r): string => $r->total, $renglones));
    }
}
