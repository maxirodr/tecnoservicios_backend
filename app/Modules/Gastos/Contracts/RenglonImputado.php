<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Contracts;

/**
 * Un renglón de gasto imputado a una obra, como lo lee Rendicion (rendicion.md §12.4.1). Importes y
 * cantidades como string decimal con signo; `rubroClave` nunca es null porque el renglón tiene obra.
 */
final readonly class RenglonImputado
{
    /**
     * @param  string  $fecha  fecha efectiva = `gasto_renglones.fecha` (facturas: la de la factura; horas y fijos: `periodo_hasta`)
     * @param  string  $origen  factura | sin_comprobante | combustible | consumo_stock | amortizacion | retiro_remito | devolucion | monto_periodo | saldo_inicial
     * @param  string  $tipoDocumento  factura | sin_documento (no hay nota de crédito: resuelta DU-G05)
     * @param  string  $cantidad  escala 3, "12.500"
     * @param  string  $precioUnitario  escala 2
     * @param  string  $total  escala 2, = round(cantidad × precio, 2)
     */
    public function __construct(
        public int $renglonId,
        public int $gastoId,
        public string $fecha,
        public string $origen,
        public string $tipoDocumento,
        public ?string $numero,
        public ?int $proveedorId,
        public ?string $proveedorNombre,
        public bool $proveedorGenerico,
        public string $concepto,
        public string $cantidad,
        public string $precioUnitario,
        public string $total,
        public string $rubroClave,
        public ?int $materialId,
        public ?string $periodoDesde,
        public ?string $periodoHasta,
        public ?string $rendidoPor,
    ) {}
}
