<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Rendicion\Domain\Calculo\MontoYGanancia;
use App\Modules\Rendicion\Domain\Calculo\ResumenDeRendicion;
use Carbon\CarbonImmutable;

/**
 * W-11 / W-44 para quien mira (rendicion.md §12.1). `verMonto` false: la clave `monto` no viaja (RN-REN-23);
 * con permiso y ningún monto cargado, `monto` es null ("Todavía no se cargó el monto de la obra.").
 */
final readonly class RendicionDeObra
{
    /**
     * @param  list<RenglonImputado>  $ultimosGastos
     * @param  array<string, bool>  $puede
     */
    public function __construct(
        public ObraResumen $obra,
        public ResumenDeRendicion $resumen,
        public bool $verMonto,
        public ?MontoYGanancia $monto,
        public array $ultimosGastos,
        public array $puede,
        public CarbonImmutable $actualizadaAt,
    ) {}
}
