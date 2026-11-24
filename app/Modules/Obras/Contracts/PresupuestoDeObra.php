<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/**
 * Un presupuesto de la obra tal como lo lee la rendición (rendicion.md §12.4.2): con su modo de carga para
 * saber si hay presupuesto por rubro, solo total o sin cargar (N10, RN-REN-07, RN-REN-12). `numero` es el
 * del presupuesto ("1-200-2026", "1-200-2026 A1"); `total`, la suma de sus montos (string decimal).
 */
final readonly class PresupuestoDeObra
{
    /**
     * @param  'sin_cargar'|'por_rubro'|'solo_total'  $modoCarga
     * @param  numeric-string  $total
     */
    public function __construct(
        public int $id,
        public string $numero,
        public ?string $revision,
        public bool $esPrincipal,
        public string $modoCarga,
        public string $total,
    ) {}
}
