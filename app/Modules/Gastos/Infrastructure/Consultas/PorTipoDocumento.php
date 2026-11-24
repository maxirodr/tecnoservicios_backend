<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use App\Modules\Gastos\Domain\Enums\TipoDocumento;
use Illuminate\Database\Query\Builder;

/** Filtro Tipo: Factura · Sin documento (RN-GAS-30). */
final readonly class PorTipoDocumento implements FiltroGastos
{
    public function __construct(private TipoDocumento $tipo) {}

    public function aplicar(Builder $gastos): void
    {
        $gastos->where('gastos.tipo_documento', $this->tipo->value);
    }
}
