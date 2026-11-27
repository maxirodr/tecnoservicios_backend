<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Domain\Enums\TipoDocumento;

/**
 * Filtros de W-33 ya validados (RN-GAS-30): `desde`/`hasta` ya con el mes en curso por defecto o null =
 * sin límite (RN-GAS-35). `agrupar`: proveedor · obra · rubro (RN-GAS-33). `pago`: impagas · pagas (RN-GAS-61).
 */
final readonly class FiltrosDeGastos
{
    public const AGRUPAR_PROVEEDOR = 'proveedor';

    public const AGRUPAR_OBRA = 'obra';

    public const AGRUPAR_RUBRO = 'rubro';

    public const POR_PAGINA = 50;

    public function __construct(
        public ?int $obraId = null,
        public bool $sinObra = false,
        public ?int $proveedorId = null,
        public ?string $rubro = null,
        public ?string $desde = null,
        public ?string $hasta = null,
        public ?TipoDocumento $tipo = null,
        public ?string $agrupar = null,
        public ?string $pago = null,
    ) {}
}
