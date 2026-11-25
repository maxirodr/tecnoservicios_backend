<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

/**
 * Lo que devuelve `MaterialesDeObra::agregarALaLista` (RN-IMP-28): el material de la lista y, si ya estaba por su
 * nombre, la cantidad presupuestada que tiene (no se cambia).
 */
final readonly class MaterialAgregado
{
    /**
     * @param  numeric-string|null  $cantidadExistente
     */
    public function __construct(
        public int $id,
        public bool $nuevo,
        public ?string $cantidadExistente,
    ) {}
}
