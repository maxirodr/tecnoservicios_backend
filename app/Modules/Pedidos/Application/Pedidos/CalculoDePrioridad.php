<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Domain\Prioridad\EstrategiaPrioridad;
use App\Modules\Pedidos\Domain\Prioridad\Manual;
use App\Modules\Pedidos\Domain\Prioridad\PorAnticipacion;
use DateTimeInterface;

/**
 * Elige la estrategia según `prioridad_manual` (RN-PED-14) y calcula la sugerida del formulario
 * (`GET /api/pedidos/prioridad-sugerida`, "Calculada: Normal · 11 días"). El backend nunca confía en la
 * prioridad calculada que mande el front (HU-027 c6).
 */
final readonly class CalculoDePrioridad
{
    public function __construct(private PlazosPrioridad $plazos) {}

    public function estrategia(bool $manual): EstrategiaPrioridad
    {
        return $manual ? new Manual : new PorAnticipacion($this->plazos);
    }

    public function calculada(?DateTimeInterface $fechaPedido, ?DateTimeInterface $fechaEntrega): ?Prioridad
    {
        if ($fechaPedido === null || $fechaEntrega === null) {
            return null;
        }

        return $this->plazos->calcular($fechaPedido, $fechaEntrega);
    }
}
