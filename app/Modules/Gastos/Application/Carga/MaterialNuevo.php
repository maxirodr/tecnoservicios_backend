<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

/** "No está en la lista": material fuera de presupuesto con su unidad (RN-GAS-14, resuelta DU-G03). */
final readonly class MaterialNuevo
{
    public function __construct(
        public string $nombre,
        public string $unidad,
    ) {}
}
