<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

/**
 * Una fila de la hoja Materiales ya leída (§5.5): el material de la lista y lo retirado hasta el corte (RN-IMP-28, 29).
 * El presupuesto va por su N.º: el id se sabe recién cuando la obra está en el sistema.
 */
final readonly class MaterialDelLote
{
    /**
     * @param  numeric-string  $cantidad
     * @param  numeric-string|null  $precio
     * @param  numeric-string|null  $retirado
     */
    public function __construct(
        public int $fila,
        public string $nombre,
        public string $unidad,
        public string $cantidad,
        public ?string $precio,
        public ?string $presupuesto,
        public ?string $retirado,
    ) {}

    public function conRetirado(): bool
    {
        return $this->retirado !== null && bccomp($this->retirado, '0', 3) === 1;
    }
}
