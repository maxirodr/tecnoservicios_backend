<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;

/**
 * Lo que se mira con todas las hojas leídas: la fecha de corte si hay gastado o retirado (§5.1, resuelta DU-I02), el
 * control contra el total de su planilla al centavo (RN-IMP-24) y el aviso de la obra sin presupuesto por rubro
 * (RN-IMP-22).
 */
final class RevisarObra
{
    public static function handle(ObraDelLote $obra): void
    {
        $faltaCorte = array_filter($obra->faltantes, static fn (Faltante $f): bool => $f->columna === C::CORTE) !== [];

        if (($obra->tieneGastado || $obra->conRetirado() > 0) && $obra->corte === null && ! $faltaCorte) {
            $obra->faltantes[] = self::faltante($obra, $obra->fila, C::CORTE, T::falta(C::CORTE));
        }

        $gastadoCompleto = array_filter($obra->faltantes, static fn (Faltante $f): bool => $f->hoja === C::HOJA_GASTADO) === [];

        if ($obra->totalPlanilla !== null && $gastadoCompleto && bccomp($obra->gastadoTotal(), $obra->totalPlanilla, 2) !== 0) {
            $obra->faltantes[] = self::faltante($obra, $obra->fila, C::TOTAL_PLANILLA, T::controlDistinto($obra->gastadoTotal(), $obra->totalPlanilla));
        }

        $porRubro = array_filter($obra->presupuestos, static fn ($p): bool => $p->modo === 'por_rubro') !== [];

        if ($obra->existente === null && ! $porRubro) {
            $obra->avisos[] = new Aviso(C::HOJA_OBRAS, $obra->fila, $obra->numero, T::SIN_PRESUPUESTO_POR_RUBRO);
        }
    }

    private static function faltante(ObraDelLote $obra, ?int $fila, string $columna, string $que): Faltante
    {
        return new Faltante(C::HOJA_OBRAS, $fila, $obra->numero, $columna, $que, Informe::ADMINISTRACION);
    }
}
