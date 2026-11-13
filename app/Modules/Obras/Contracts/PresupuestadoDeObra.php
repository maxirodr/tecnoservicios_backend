<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Lo presupuestado de la obra para la rendición (semana 3): suma de todos sus presupuestos, rubro
 * por rubro (EPP fuera: no se presupuesta), más lo cargado "solo total" en `sinDesglose` (RN-OBR-37).
 * Importes como string decimal (ADR-008).
 */
final readonly class PresupuestadoDeObra
{
    /**
     * @param  array<string, string>  $porRubro  clave de rubro => importe
     */
    public function __construct(
        public array $porRubro,
        public string $sinDesglose,
        public string $total,
    ) {}
}
