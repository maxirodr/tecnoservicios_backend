<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

/**
 * Presupuesto ya normalizado en el borde (RN-OBR-11, RN-OBR-14): número `2-131-2026 A1`, revisión
 * `REV01` o null, y los ids de adjuntos temporales de la oferta técnica y de la aprobación.
 */
final readonly class DatosPresupuesto
{
    public function __construct(
        public string $numero,
        public ?string $revision,
        public ?int $oferta,
        public ?string $descripcion,
        public ?DatosAprobacion $aprobacion,
    ) {}
}
