<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Enums\TipoDocumento;
use Illuminate\Database\Query\Builder;

/** Filtro Pago: Impagas · Pagas (RN-GAS-61, DU-C03). Deja solo facturas, que son las que tienen estado de pago. */
final readonly class PorPago implements FiltroGastos
{
    public const IMPAGAS = 'impagas';

    public const PAGAS = 'pagas';

    public function __construct(private string $pago) {}

    public function aplicar(Builder $gastos): void
    {
        $gastos->where('gastos.tipo_documento', TipoDocumento::Factura->value);
        $this->pago === self::PAGAS ? $gastos->whereNotNull('gastos.pagado_at') : $gastos->whereNull('gastos.pagado_at');
    }
}
