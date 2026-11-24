<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * Filtro de `GastadoDeObras::renglonesDeObra` (rendicion.md §12.4.1). Las fechas son la fecha efectiva
 * del renglón (`gasto_renglones.fecha`, resuelta DU-G01), inclusive, "AAAA-MM-DD".
 */
final readonly class FiltroRenglones
{
    public const ORDEN_FECHA = 'fecha';

    public const ORDEN_PROVEEDOR = 'proveedor';

    public const ORDEN_TOTAL = 'total';

    /**
     * @param  list<string>|null  $rubroClaves  null = todos (Rendicion pasa ['insumos', 'epp'] para INSUMOS)
     * @param  'fecha'|'proveedor'|'total'  $orden
     * @param  'asc'|'desc'  $dir
     * @param  int|null  $limite  10 para "Últimos gastos" (resuelta DU-R07)
     */
    public function __construct(
        public ?array $rubroClaves = null,
        public ?string $desde = null,
        public ?string $hasta = null,
        public bool $soloConMaterial = false,
        public string $orden = self::ORDEN_FECHA,
        public string $dir = 'desc',
        public ?int $limite = null,
    ) {}
}
