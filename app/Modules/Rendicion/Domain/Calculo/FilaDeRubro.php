<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

/**
 * Una fila del resumen por rubro (W-11, W-44, encabezado de W-36): N1, N4, N6, N8, N10. Presupuestado,
 * saldo y % son null cuando la obra no tiene presupuesto por rubro (solo total o sin cargar).
 */
final readonly class FilaDeRubro
{
    /**
     * @param  list<string>  $incluye  claves que suman en este rubro en el resumen (INSUMOS ← EPP)
     * @param  numeric-string|null  $presupuestado
     * @param  numeric-string  $gastado
     * @param  numeric-string|null  $saldo
     * @param  numeric-string|null  $porcentaje
     */
    public function __construct(
        public string $clave,
        public string $nombre,
        public array $incluye,
        public bool $cargaPorPeriodo,
        public ?string $presupuestado,
        public string $gastado,
        public ?string $saldo,
        public ?string $porcentaje,
        public bool $pasado,
        public bool $sinPresupuesto,
    ) {}
}
