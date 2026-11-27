<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\Enums\Origen;

/**
 * GastoAlta (gastos.md §12.2) con la forma ya validada en el borde; las reglas de negocio las aplica
 * `ValidarGasto`. Al corregir se arma con lo guardado más lo que cambió. Fechas "AAAA-MM-DD".
 */
final readonly class DatosGasto
{
    /**
     * @param  list<int>  $adjuntos
     * @param  list<DatosRenglon>  $renglones
     */
    public function __construct(
        public Origen $origen,
        public ?string $numero,
        public ?string $fecha,
        public ?int $proveedorId,
        public ?string $rendidoPor,
        public ?string $periodoDesde,
        public ?string $periodoHasta,
        public array $adjuntos,
        public array $renglones,
        public bool $confirmarRemitoRepetido = false,
        public bool $confirmarSuperposicion = false,
        public ?string $concepto = null,
    ) {}
}
