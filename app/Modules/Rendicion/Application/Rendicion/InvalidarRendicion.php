<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Domain\Events\EventoDeGasto;
use App\Modules\Obras\Domain\Events\PresupuestoMontosCambiados;

/**
 * Después del commit (RN-REN-60, rendicion.md §8): `GastoImputado`, `GastoCorregido` (obras de antes y de
 * después) y `GastoAnulado` invalidan la rendición de cada obra afectada; `PresupuestoMontosCambiados`, la de
 * su obra.
 */
final readonly class InvalidarRendicion
{
    public function __construct(private GastadoEnCache $cache) {}

    public function deGasto(EventoDeGasto $evento): void
    {
        $this->cache->olvidar($evento->obraIds);
    }

    public function dePresupuesto(PresupuestoMontosCambiados $evento): void
    {
        $this->cache->olvidar([$evento->obraId]);
    }
}
