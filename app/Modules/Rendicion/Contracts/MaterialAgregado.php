<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

/**
 * Lo que devuelve `MaterialesDeObra::agregarALaLista` (RN-IMP-28): el material de la lista y, si ya estaba por su
 * nombre, la cantidad presupuestada que tiene (no se cambia), como texto decimal de 3 decimales.
 */
final readonly class MaterialAgregado
{
    public function __construct(
        public int $id,
        public bool $nuevo,
        public ?string $cantidadExistente,
    ) {}
}
