<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Historial;

use DateTimeInterface;

/** Una acción del historial (W-23): cuándo, quién y una línea por dato que cambió (HU-016 criterio 2). */
final readonly class EntradaHistorial
{
    /**
     * @param  list<string>  $lineas
     */
    public function __construct(
        public int $id,
        public DateTimeInterface $cuando,
        public ?int $quienId,
        public ?string $quienNombre,
        public array $lineas,
    ) {}
}
