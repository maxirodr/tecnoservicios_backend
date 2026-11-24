<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

/** La lista cerrada de rubros de su rendición (rendicion.md §12.4.3), en el orden de su hoja General. */
interface CatalogoDeRubros
{
    /**
     * Los 13 rubros a los que se imputa un renglón. `cargaPorPeriodo` (FIJOS, HORAS LIQUIDADAS) no se
     * ofrece en una factura (RN-GAS-12).
     *
     * @return list<RubroDelCatalogo>
     */
    public function imputables(): array;

    /**
     * Los 12 que se presupuestan (sin EPP).
     *
     * @return list<RubroDelCatalogo>
     */
    public function presupuestables(): array;

    /** En qué rubro suma en el resumen ("epp" → "insumos"); null si en el propio. */
    public function resumeEn(string $clave): ?string;
}
