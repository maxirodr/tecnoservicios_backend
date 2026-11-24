<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

use App\Modules\Obras\Contracts\PresupuestoDeObra;

/**
 * Cómo está presupuestada la obra (rendicion.md §12.1 `situacion`, RN-REN-07, 10, 11): según el modo de
 * carga de sus presupuestos. Los "sin cargar" no cuentan (RN-REN-12).
 */
enum Situacion: string
{
    case SinPresupuesto = 'sin_presupuesto';
    case PorRubro = 'por_rubro';
    case SoloTotal = 'solo_total';
    case Mixta = 'mixta';

    public const MODO_POR_RUBRO = 'por_rubro';

    public const MODO_SOLO_TOTAL = 'solo_total';

    public const MODO_SIN_CARGAR = 'sin_cargar';

    /**
     * @param  list<PresupuestoDeObra>  $presupuestos
     */
    public static function de(array $presupuestos): self
    {
        $modos = array_map(static fn (PresupuestoDeObra $p): string => $p->modoCarga, $presupuestos);
        $porRubro = in_array(self::MODO_POR_RUBRO, $modos, true);
        $soloTotal = in_array(self::MODO_SOLO_TOTAL, $modos, true);

        return match (true) {
            $porRubro && $soloTotal => self::Mixta,
            $porRubro => self::PorRubro,
            $soloTotal => self::SoloTotal,
            default => self::SinPresupuesto,
        };
    }

    /** Hay al menos un presupuesto por rubro: los rubros se comparan y se marcan (N10). */
    public function comparaRubros(): bool
    {
        return $this === self::PorRubro || $this === self::Mixta;
    }

    public function tienePresupuesto(): bool
    {
        return $this !== self::SinPresupuesto;
    }
}
