<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use Carbon\CarbonImmutable;

/**
 * Columna "Documento" de W-11 y W-36 (RN-REN-29, resuelta DU-R07): las mismas etiquetas que W-33 y W-34
 * (`gastos.md` RN-GAS-29), con la misma función de Gastos para que no se separen. Proveedor: el nombre, o
 * null ("—") si es el genérico de los gastos sin comprobante.
 */
final class DocumentoDelRenglon
{
    public static function etiqueta(RenglonImputado $renglon): string
    {
        $origen = Origen::tryFrom($renglon->origen);

        if ($origen === null) {
            return 'Sin documento';
        }

        $periodo = $origen === Origen::MontoPeriodo;

        return EtiquetaDocumento::de(
            $origen,
            $renglon->numero,
            $periodo ? CarbonImmutable::parse($renglon->periodoDesde ?? $renglon->fecha) : null,
            $periodo ? CarbonImmutable::parse($renglon->periodoHasta ?? $renglon->fecha) : null,
        );
    }

    public static function proveedor(RenglonImputado $renglon): ?string
    {
        return $renglon->proveedorGenerico ? null : $renglon->proveedorNombre;
    }
}
