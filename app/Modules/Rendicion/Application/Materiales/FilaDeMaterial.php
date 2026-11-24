<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Rendicion\Domain\Models\Material;

/**
 * Una fila de la lista de W-48 (RN-REN-50): retirado por fecha (solo las columnas visibles), lo agrupado en
 * "Hasta", total retirado (N17), resta llevar (N18) y total en pesos (N19).
 */
final readonly class FilaDeMaterial
{
    /**
     * @param  array<string, numeric-string>  $retiradoPorFecha
     * @param  numeric-string|null  $retiradoHasta
     * @param  numeric-string  $totalRetirado
     * @param  numeric-string  $restaLlevar
     * @param  numeric-string  $total
     */
    public function __construct(
        public Material $material,
        public ?string $presupuestoNumero,
        public array $retiradoPorFecha,
        public ?string $retiradoHasta,
        public string $totalRetirado,
        public string $restaLlevar,
        public string $total,
    ) {}
}
