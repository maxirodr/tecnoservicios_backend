<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\Origen;

/**
 * Rubro de cada renglón según la carga (gastos.md §4.3): con obra es obligatorio; en una factura y en las
 * demás cargas, uno de los 11 que se imputan (RN-GAS-12); la amortización va siempre a AMORTIZACION
 * (RN-GAS-23) y un monto por período, a FIJOS o HORAS LIQUIDADAS (RN-GAS-27). EPP se guarda como EPP.
 */
final readonly class RubrosDeLaCarga
{
    public const SOLO_POR_PERIODO = 'FIJOS y HORAS LIQUIDADAS se cargan como monto por período, desde la rendición.';

    public const ELEGI_EL_RUBRO = 'Elegí el rubro.';

    public function __construct(private Rubros $rubros) {}

    public function rubroDe(Origen $origen, DatosRenglon $renglon, Errores $errores): ?int
    {
        if ($origen === Origen::Amortizacion) {
            return $this->rubros->idDe(Rubros::AMORTIZACION);
        }

        $rubro = $renglon->rubroClave === null ? null : $this->rubros->porClave($renglon->rubroClave);

        if ($renglon->rubroClave === null) {
            if ($renglon->obraId !== null || $origen === Origen::MontoPeriodo) {
                $errores->renglon($renglon->posicion, 'rubro', self::ELEGI_EL_RUBRO);
            }

            return null;
        }

        if ($rubro === null || ($origen === Origen::MontoPeriodo && ! $rubro->cargaPorPeriodo)) {
            $errores->renglon($renglon->posicion, 'rubro', self::ELEGI_EL_RUBRO);

            return null;
        }

        if ($origen !== Origen::MontoPeriodo && $rubro->cargaPorPeriodo) {
            $errores->renglon($renglon->posicion, 'rubro', self::SOLO_POR_PERIODO);
        }

        return $rubro->id;
    }
}
