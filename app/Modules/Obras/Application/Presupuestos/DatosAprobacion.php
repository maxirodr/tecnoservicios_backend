<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Domain\Enums\AprobacionTipo;

/** Documento de aprobación de un presupuesto (obras.md §4.3): tipo + número y/o adjunto (RN-OBR-16). */
final readonly class DatosAprobacion
{
    public function __construct(
        public AprobacionTipo $tipo,
        public ?string $numero,
        public ?int $adjunto,
    ) {}
}
