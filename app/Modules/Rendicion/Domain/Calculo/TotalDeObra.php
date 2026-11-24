<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

/**
 * La fila "Total de la obra" (N3, N5, N7, N9, N11, N12). Sin presupuesto cargado: presupuestado, saldo y %
 * null. `rubrosPasados` null si la obra no tiene presupuesto por rubro ("—" en W-12).
 */
final readonly class TotalDeObra
{
    /**
     * @param  numeric-string|null  $presupuestado
     * @param  numeric-string  $gastado
     * @param  numeric-string|null  $saldo
     * @param  numeric-string|null  $porcentaje
     */
    public function __construct(
        public ?string $presupuestado,
        public string $gastado,
        public ?string $saldo,
        public ?string $porcentaje,
        public bool $pasado,
        public ?int $rubrosPasados,
    ) {}
}
