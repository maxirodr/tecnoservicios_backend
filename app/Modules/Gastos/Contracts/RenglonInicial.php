<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * Un renglón de la carga inicial: gastado (cantidad 1, precio = el importe, cualquier rubro de los 13) o retirado
 * (cantidad = lo retirado, precio 0, MATERIALES y el material de la lista). Cantidad escala 3, precio escala 2.
 */
final readonly class RenglonInicial
{
    /**
     * @param  numeric-string  $cantidad
     * @param  numeric-string  $precio
     */
    public function __construct(
        public string $rubroClave,
        public string $concepto,
        public string $cantidad,
        public string $precio,
        public ?int $materialId = null,
    ) {}
}
