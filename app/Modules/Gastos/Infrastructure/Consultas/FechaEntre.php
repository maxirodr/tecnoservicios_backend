<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure\Consultas;

use Illuminate\Database\Query\Builder;

/** Fecha del comprobante desde → hasta, inclusive; cualquiera de las dos puede faltar (RN-GAS-30, 35). */
final readonly class FechaEntre implements FiltroGastos
{
    public function __construct(
        private ?string $desde,
        private ?string $hasta,
    ) {}

    public function aplicar(Builder $gastos): void
    {
        if ($this->desde !== null) {
            $gastos->where('gastos.fecha', '>=', $this->desde);
        }

        if ($this->hasta !== null) {
            $gastos->where('gastos.fecha', '<=', $this->hasta);
        }
    }
}
