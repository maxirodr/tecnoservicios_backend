<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Ports;

/**
 * Puerto que implementa Rendicion (semana 3, rendicion.md §12.4.2): un adicional con materiales del
 * control por cantidad no se quita (RN-OBR-24, RN-REN-53).
 */
interface MaterialesDePresupuesto
{
    public function tieneMateriales(int $presupuestoId): bool;
}
