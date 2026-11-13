<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Ports;

/**
 * Una fila de "Gastado por obra" (RN-PRV-15): la obra (null = "(sin obra)"), cuántos renglones y el
 * importe neto sin IVA como string decimal (ADR-008).
 */
final readonly class FilaGastado
{
    /**
     * @param  numeric-string  $importe
     */
    public function __construct(
        public ?ObraGastada $obra,
        public int $renglones,
        public string $importe,
    ) {}
}
