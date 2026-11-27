<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

/** Un renglón listo para guardar: fecha efectiva, cantidad escala 3, precio y total escala 2 (ADR-008). */
final readonly class RenglonAGuardar
{
    /**
     * @param  numeric-string  $cantidad
     * @param  numeric-string  $precioUnitario
     * @param  numeric-string  $total
     */
    public function __construct(
        public int $posicion,
        public ?int $id,
        public string $fecha,
        public string $concepto,
        public string $cantidad,
        public string $precioUnitario,
        public string $total,
        public ?int $obraId,
        public ?int $rubroId,
        public ?int $materialId,
        public ?MaterialNuevo $materialNuevo,
        public ?string $detalle,
        public ?string $empleado = null,
    ) {}
}
