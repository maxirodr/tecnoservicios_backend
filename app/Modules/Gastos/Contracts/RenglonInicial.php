<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * Un renglón de la carga inicial: gastado (cantidad 1, precio = el importe, cualquier rubro de los 13) o retirado
 * (cantidad = lo retirado, precio 0, MATERIALES y el material de la lista).
 * Cantidad hasta 3 decimales y precio hasta 2, como texto decimal: Gastos los valida (ADR-008).
 */
final readonly class RenglonInicial
{
    public function __construct(
        public string $rubroClave,
        public string $concepto,
        public string $cantidad,
        public string $precio,
        public ?int $materialId = null,
    ) {}
}
